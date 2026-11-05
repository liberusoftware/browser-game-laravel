<?php

use App\Filament\Admin\Pages\GameSetup;
use App\Filament\App\Pages\AccountSetup;
use App\Filament\App\Pages\QuestJournal;
use App\Models\Item;
use App\Models\Player;
use App\Models\Quest;
use App\Models\User;
use App\Services\QuestService;
use App\Services\StarterAdventureService;
use Database\Seeders\StarterAdventureSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\RolesPermissions\Models\Role;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

it('seeds a coherent adventure repeatedly without replacing edited content or creating login accounts', function (): void {
    $this->seed(StarterAdventureSeeder::class);
    $quest = Quest::where('starter_key', 'welcome-to-dawnvale')->firstOrFail();
    $quest->update(['description' => 'An administrator edited this scene.']);
    $this->seed(StarterAdventureSeeder::class);

    expect(Quest::whereNotNull('starter_key')->count())->toBe(39)
        ->and(Item::whereNotNull('starter_key')->count())->toBe(97)
        ->and($quest->fresh()->description)->toBe('An administrator edited this scene.')
        ->and(User::count())->toBe(0)
        ->and(Player::count())->toBe(0);
    foreach (Quest::all() as $quest) {
        expect($quest->itemReward)->not->toBeNull();
    }
});

it('creates each class with its stats and grants a starter kit only once', function (string $class): void {
    $this->seed(StarterAdventureSeeder::class);
    $user = User::factory()->create();
    $this->actingAs($user);
    $form = ['name' => 'Dawnvale Explorer', 'team_name' => 'Explorers', 'character_class' => $class, 'generate_api_token' => false];
    Livewire::test(AccountSetup::class)->set('data', $form)->call('save')->assertHasNoFormErrors();
    $player = $user->fresh()->player;
    $definition = config('starter-adventure.classes')[$class];

    expect($player->character_class)->toBe($class)
        ->and($player->health)->toBe($definition['stats']['health'])
        ->and($player->intelligence)->toBe($definition['stats']['intelligence'])
        ->and($player->items()->count())->toBe(3)
        ->and($player->items()->where('starter_key', $definition['weapon'])->exists())->toBeTrue();

    $player->update(['health' => 40, 'experience' => 80]);
    Livewire::test(AccountSetup::class)->set('data', $form)->call('save')->assertHasNoFormErrors();
    expect($player->fresh()->health)->toBe(40)
        ->and($player->fresh()->experience)->toBe(80)
        ->and((int) $player->items()->where('starter_key', 'silverleaf')->first()->pivot->quantity)->toBe(3);
})->with(['warrior', 'ranger', 'mage', 'rogue', 'paladin', 'cleric', 'druid', 'bard']);

it('supports the whole story with progression and inventory rewards for the signed in player', function (): void {
    $this->seed(StarterAdventureSeeder::class);
    $user = User::factory()->create();
    $player = Player::factory()->create(['email' => $user->email, 'level' => 1, 'experience' => 0]);
    app(StarterAdventureService::class)->grantStarterKit($player, 'warrior');
    $this->actingAs($user);
    $journal = Livewire::test(QuestJournal::class)->assertSee('Dawnvale starter adventure');
    foreach (config('starter-adventure.quests') as $key => $chapter) {
        $quest = Quest::where('starter_key', $key)->firstOrFail();
        $journal->call('accept', $quest->id)->assertHasNoErrors()
            ->call('completeAdventure', $quest->id, $chapter['answer'])->assertHasNoErrors();
    }

    expect($player->completedQuests()->count())->toBe(39)
        ->and($player->fresh()->experience)->toBe(7480)
        ->and($player->fresh()->level)->toBe(75)
        ->and((int) $player->items()->where('starter_key', 'silverleaf')->first()->pivot->quantity)->toBe(5)
        ->and($player->items()->where('starter_key', 'dawnvale-medal')->exists())->toBeTrue();

    $journal->call('gotoPage', 4)->assertSee('Homeward Sails')->assertSee('Completed');

    $first = Quest::where('starter_key', 'welcome-to-dawnvale')->first();
    $journal->call('completeAdventure', $first->id, 'check-pack')->assertHasErrors(['adventure']);
    expect($player->fresh()->experience)->toBe(7480);
    $this->seed(StarterAdventureSeeder::class);
    expect($player->completedQuests()->count())->toBe(39);
});

it('rejects skipped chapters and wrong choices without awarding progress', function (): void {
    $this->seed(StarterAdventureSeeder::class);
    $user = User::factory()->create();
    $player = Player::factory()->create(['email' => $user->email, 'experience' => 0]);
    $this->actingAs($user);
    $first = Quest::where('starter_key', 'welcome-to-dawnvale')->first();
    $second = Quest::where('starter_key', 'silverleaf-trail')->first();
    Livewire::test(QuestJournal::class)
        ->call('accept', $second->id)->assertHasErrors(['adventure'])
        ->call('accept', $first->id)
        ->call('completeAdventure', $first->id, 'rush-out')->assertHasErrors(['adventure']);
    expect($player->fresh()->experience)->toBe(0)
        ->and($player->completedQuests()->count())->toBe(0);
});

it('cannot claim another players accepted chapter or bypass the story through the quest service', function (): void {
    $this->seed(StarterAdventureSeeder::class);
    $quest = Quest::where('starter_key', 'welcome-to-dawnvale')->first();
    $otherPlayer = Player::factory()->create();
    $otherPlayer->quests()->attach($quest, ['status' => 'in-progress']);
    $user = User::factory()->create();
    Player::factory()->create(['email' => $user->email]);
    $this->actingAs($user);
    Livewire::test(QuestJournal::class)->call('completeAdventure', $quest->id, 'check-pack')->assertHasErrors(['adventure']);
    expect(fn () => app(QuestService::class)->completeQuest($otherPlayer, $quest))->toThrow(ValidationException::class);
});

it('rejects an unknown character class', function (): void {
    $this->actingAs(User::factory()->create());
    Livewire::test(AccountSetup::class)
        ->set('data', ['name' => 'Explorer', 'team_name' => 'Explorers', 'character_class' => 'overpowered', 'generate_api_token' => false])
        ->call('save')->assertHasFormErrors(['character_class']);
    expect(Player::count())->toBe(0);
});

it('allows a super administrator to install the adventure from the wizard', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $user->assignRole('super_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);
    Livewire::test(GameSetup::class)->call('installStarterAdventure')->call('installStarterAdventure')->assertHasNoErrors();
    expect(Quest::whereNotNull('starter_key')->count())->toBe(39);
});

it('keeps every class kit and quest branch connected to valid catalog entries', function (): void {
    $classes = config('starter-adventure.classes');
    $items = config('starter-adventure.items');
    $quests = config('starter-adventure.quests');
    expect($classes)->toHaveCount(8);
    foreach ($classes as $class) {
        expect($items)->toHaveKey($class['weapon'])
            ->and($class['stats']['health'])->toBe($class['stats']['max_health'])
            ->and($class['stats']['mana'])->toBe($class['stats']['max_mana']);
    }
    foreach ($quests as $key => $quest) {
        expect($items)->toHaveKey($quest['reward'])
            ->and($quest['choices'])->toHaveKey($quest['answer'])
            ->and($quest['hint'])->not->toBeEmpty();
        $visited = [$key];
        while ($quest['requires'] !== null) {
            expect($quests)->toHaveKey($quest['requires'])
                ->and($visited)->not->toContain($quest['requires']);
            $visited[] = $quest['requires'];
            $quest = $quests[$quest['requires']];
        }
    }
});

it('adds the expanded content to an original catalog while preserving character progress', function (): void {
    $items = config('starter-adventure.items');
    $quests = config('starter-adventure.quests');
    config(['starter-adventure.items' => array_slice($items, 0, 8, true), 'starter-adventure.quests' => array_slice($quests, 0, 5, true)]);
    $this->seed(StarterAdventureSeeder::class);
    $quest = Quest::where('starter_key', 'guardian-of-dawnvale')->firstOrFail();
    $quest->update(['description' => 'Existing custom story']);
    $player = Player::factory()->create(['level' => 5, 'experience' => 400, 'character_class' => 'mage']);
    $player->quests()->attach($quest, ['status' => 'completed']);
    $medal = Item::where('starter_key', 'dawnvale-medal')->firstOrFail();
    $player->items()->attach($medal, ['quantity' => 2]);

    config(['starter-adventure.items' => $items, 'starter-adventure.quests' => $quests]);
    $this->seed(StarterAdventureSeeder::class);
    $this->seed(StarterAdventureSeeder::class);
    expect(Quest::count())->toBe(39)
        ->and(Item::count())->toBe(97)
        ->and($quest->fresh()->description)->toBe('Existing custom story')
        ->and($player->fresh()->level)->toBe(5)
        ->and($player->fresh()->experience)->toBe(400)
        ->and($player->fresh()->character_class)->toBe('mage')
        ->and((int) $player->items()->whereKey($medal->id)->first()->pivot->quantity)->toBe(2)
        ->and(app(StarterAdventureService::class)->prerequisiteCompleted($player, Quest::where('starter_key', 'chapel-bells')->firstOrFail()))->toBeTrue();
});
