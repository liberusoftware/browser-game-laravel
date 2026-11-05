<?php

use App\Filament\Admin\Pages\GameSetup;
use App\Filament\App\Pages\AccountSetup;
use App\Filament\App\Pages\AdventureGuide;
use App\Filament\App\Pages\QuestJournal;
use App\Filament\App\Widgets\ActiveQuestsWidget;
use App\Models\Player;
use App\Models\Quest;
use App\Models\User;
use App\Settings\GameSettings;
use Filament\Facades\Filament;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\RolesPermissions\Models\Role;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

it('renders the adventure overview and guide for an account without a player', function (): void {
    $this->actingAs(User::factory()->create());
    $this->get(route('filament.app.pages.dashboard'))->assertSuccessful()->assertSee('Every legend starts somewhere.');
    Livewire::test(AdventureGuide::class)->assertSee('Your first adventure starts here');
});

it('saves guide completion only for the current account', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AdventureGuide::class)->call('complete')->assertRedirect();

    expect($user->fresh()->game_guide_completed_at)->not->toBeNull()
        ->and($other->fresh()->game_guide_completed_at)->toBeNull();
});

it('prepares a player during setup without duplicating existing progress', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $settings = app(GameSettings::class);
    $settings->starting_level = 3;
    $settings->starting_experience = 200;
    $settings->save();
    $form = ['name' => 'Explorer', 'team_name' => 'Explorers', 'generate_api_token' => false];
    Livewire::test(AccountSetup::class)->set('data', $form)->call('save')->assertHasNoFormErrors();
    $player = $user->fresh()->player;
    expect($player)->not->toBeNull()
        ->and($player->level)->toBe(3)
        ->and($player->experience)->toBe(200);
    $player->update(['level' => 5, 'experience' => 450]);

    Livewire::test(AccountSetup::class)->set('data', $form)->call('save');
    expect(Player::where('email', $user->email)->count())->toBe(1)
        ->and($player->fresh()->level)->toBe(5)
        ->and($player->fresh()->experience)->toBe(450);
});

it('respects disabled player registration in account setup', function (): void {
    $settings = app(GameSettings::class);
    $settings->player_registration_enabled = false;
    $settings->save();
    $user = User::factory()->create();
    $this->actingAs($user);
    Livewire::test(AccountSetup::class)->set('data', ['name' => 'Explorer', 'team_name' => 'Explorers', 'generate_api_token' => false])->call('save');
    expect($user->fresh()->player)->toBeNull();
});

it('accepts quests for the authenticated player once and enforces the active quest limit', function (): void {
    $user = User::factory()->create();
    $player = Player::factory()->create(['email' => $user->email]);
    $otherPlayer = Player::factory()->create();
    $quests = Quest::factory()->count(2)->create();
    $settings = app(GameSettings::class);
    $settings->max_active_quests = 1;
    $settings->save();
    $this->actingAs($user);

    Livewire::test(QuestJournal::class)->call('accept', $quests[0]->id)->call('accept', $quests[0]->id)->call('accept', $quests[1]->id);
    expect($player->activeQuests()->count())->toBe(1)
        ->and($otherPlayer->quests()->count())->toBe(0);
});

it('does not reveal another players active quests to an account without a character', function (): void {
    $player = Player::factory()->create();
    $quest = Quest::factory()->create(['name' => 'Private active quest']);
    $player->quests()->attach($quest, ['status' => 'in-progress']);
    $this->actingAs(User::factory()->create());
    Livewire::test(ActiveQuestsWidget::class)->assertDontSee('Private active quest');
});

it('rejects quest acceptance without a player', function (): void {
    $this->actingAs(User::factory()->create());
    Livewire::test(QuestJournal::class)->call('accept', Quest::factory()->create()->id)->assertForbidden();
});

it('denies global game setup to ordinary players', function (): void {
    $this->actingAs(User::factory()->create());
    expect(GameSetup::canAccess())->toBeFalse();
    Livewire::test(GameSetup::class)->assertForbidden();
});

it('validates and persists game configuration for a super administrator', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $user->assignRole('super_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    Livewire::test(GameSetup::class)
        ->set('data.experience_multiplier', 0)
        ->call('save')
        ->assertHasFormErrors(['experience_multiplier'])
        ->set('data.experience_multiplier', 2)
        ->set('data.max_active_quests', 4)
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(GameSettings::class);
    expect(app(GameSettings::class)->experience_multiplier)->toBe(2)
        ->and(app(GameSettings::class)->max_active_quests)->toBe(4);
});

it('renders earned progress and separates active quests from completed adventures', function (): void {
    $user = User::factory()->create();
    $player = Player::factory()->create(['email' => $user->email, 'level' => 3, 'experience' => 250]);
    $active = Quest::factory()->create(['name' => 'Find the hidden trail']);
    $completed = Quest::factory()->create(['name' => 'A finished adventure']);
    $player->quests()->attach($active, ['status' => 'in-progress']);
    $player->quests()->attach($completed, ['status' => 'completed']);
    $this->actingAs($user);

    $this->get(route('filament.app.pages.dashboard'))->assertSuccessful()->assertSee('50 XP to level 4');
    Livewire::test(ActiveQuestsWidget::class)->assertSee('Find the hidden trail')->assertDontSee('A finished adventure');
    Livewire::test(QuestJournal::class)->assertSee('Completed')->assertSee('In progress');
});
