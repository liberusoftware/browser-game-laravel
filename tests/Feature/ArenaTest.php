<?php

use App\Filament\App\Pages\BattleArena;
use App\Models\Battle;
use App\Models\Item;
use App\Models\Npc;
use App\Models\Player;
use App\Models\User;
use App\Services\ArenaService;
use App\Settings\GameSettings;
use Database\Seeders\NpcSeeder;
use Database\Seeders\StarterAdventureSeeder;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function arenaTestPlayer(array $attributes = []): Player
{
    return Player::factory()->create(array_merge([
        'level' => 1, 'experience' => 0, 'health' => 100, 'max_health' => 100,
        'mana' => 50, 'max_mana' => 50, 'strength' => 15, 'defense' => 10, 'agility' => 10,
        'intelligence' => 12, 'stat_points' => 0, 'arena_opt_in' => true,
    ], $attributes));
}

beforeEach(function (): void {
    $this->seed([StarterAdventureSeeder::class, NpcSeeder::class]);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $settings = app(GameSettings::class);
    $settings->pvp_enabled = true;
    $settings->save();
});

it('seeds sixty distinct NPCs without replacing administrator edits or creating player accounts', function (): void {
    $npc = Npc::first();
    $npc->update(['description' => 'A customized guardian']);
    $this->seed(NpcSeeder::class);
    expect(Npc::count())->toBe(60)->and($npc->fresh()->description)->toBe('A customized guardian')->and(Player::count())->toBe(0);
    foreach (Npc::with('lootItem')->get() as $npc) {
        expect($npc->lootItem)->not->toBeNull()
            ->and($npc->stats['health'])->toBeGreaterThan(0)
            ->and($npc->behavior)->toBeIn(['balanced', 'aggressive', 'defensive', 'mystic']);
    }
});

it('renders the arena with encounters, loadout and reports for a signed in player', function (): void {
    $user = User::factory()->create();
    arenaTestPlayer(['email' => $user->email]);
    $this->actingAs($user)->get(BattleArena::getUrl(panel: 'app'))->assertSuccessful()->assertSee('Practice Sentinel')->assertSee('Meadow Fenceguard')->assertSee('Prepare your loadout');
});

it('gives accounts without characters a setup action', function (): void {
    $this->actingAs(User::factory()->create());
    Livewire::test(BattleArena::class)->assertSee('Prepare my character');
});

it('plays NPC turns and awards cumulative XP, gold and loot once on victory', function (): void {
    $service = app(ArenaService::class);
    $player = arenaTestPlayer(['strength' => 100, 'experience' => 90]);
    $npc = Npc::first();
    $npc->update(['stats' => array_merge($npc->stats, ['health' => 1])]);
    $battle = $service->startNpc($player, $npc->id);
    $result = $service->act($player, $battle->id, 'strike', 0);
    expect($result->arena_status)->toBe('completed')->and($result->winner_id)->toBe($player->id)
        ->and($player->fresh()->experience)->toBe(110)->and($player->fresh()->level)->toBe(2)
        ->and((int) $player->resources()->where('resource_type', 'gold')->value('quantity'))->toBe(15)
        ->and($player->items()->whereKey($npc->loot_item_id)->exists())->toBeTrue();
    expect(fn () => $service->act($player, $battle->id, 'strike', 0))->toThrow(ValidationException::class);
    expect($player->fresh()->experience)->toBe(110);

    $this->travel(11)->seconds();
    $repeat = $service->startNpc($player->fresh(), $npc->id);
    $repeat = $service->act($player, $repeat->id, 'strike', 0);
    expect($repeat->experience_gained)->toBe(0)->and($repeat->gold_gained)->toBe(0)->and($player->fresh()->experience)->toBe(110);
});

it('blocks stale turns and invalid or unaffordable abilities without changing battle state', function (): void {
    $service = app(ArenaService::class);
    $player = arenaTestPlayer(['mana' => 0]);
    $battle = $service->startNpc($player, Npc::first()->id);
    expect(fn () => $service->act($player, $battle->id, 'arcane', 0))->toThrow(ValidationException::class);
    expect(fn () => $service->act($player, $battle->id, 'invented-power', 0))->toThrow(ValidationException::class);
    $service->act($player, $battle->id, 'guard', 0);
    expect($battle->fresh()->arena_turn)->toBe(1)->and($battle->fresh()->arena_state['attacker']['mp'])->toBe(8);
    expect(fn () => $service->act($player, $battle->id, 'guard', 0))->toThrow(ValidationException::class);
    expect($battle->fresh()->arena_turn)->toBe(1);
});

it('makes NPCs use their behavior and records both sides in the chronicle', function (): void {
    $service = app(ArenaService::class);
    $player = arenaTestPlayer(['level' => 6]);
    $npc = Npc::where('behavior', 'mystic')->orderBy('level')->first();
    $battle = $service->startNpc($player, $npc->id);
    $battle = $service->act($player, $battle->id, 'guard', 0);
    expect($battle->battle_log)->toHaveCount(2)
        ->and($battle->battle_log[1]['action'])->toBe('arcane')
        ->and($battle->arena_state['attacker']['guarding'])->toBeFalse();
});

it('requires PvP enabled, opt in, level matching and a distinct opponent', function (): void {
    $service = app(ArenaService::class);
    $attacker = arenaTestPlayer();
    $defender = arenaTestPlayer(['arena_opt_in' => false]);
    expect(fn () => $service->challenge($attacker, $attacker->id))->toThrow(ValidationException::class);
    expect(fn () => $service->challenge($attacker, $defender->id))->toThrow(ValidationException::class);
    $defender->update(['arena_opt_in' => true, 'level' => 20]);
    expect(fn () => $service->challenge($attacker, $defender->id))->toThrow(ValidationException::class);
    $defender->update(['level' => 1]);
    $settings = app(GameSettings::class);
    $settings->pvp_enabled = false;
    $settings->save();
    expect(fn () => $service->challenge($attacker, $defender->id))->toThrow(ValidationException::class);
    expect(Battle::count())->toBe(0);
});

it('requires defender acceptance and alternates actual player turns while preserving world health', function (): void {
    $service = app(ArenaService::class);
    $attacker = arenaTestPlayer(['agility' => 30, 'health' => 40, 'mana' => 20]);
    $defender = arenaTestPlayer(['agility' => 5]);
    $battle = $service->challenge($attacker, $defender->id);
    expect($battle->arena_status)->toBe('pending');
    expect(fn () => $service->act($attacker, $battle->id, 'strike', 0))->toThrow(ValidationException::class);
    $battle = $service->respond($defender, $battle->id, true);
    expect($battle->turn_player_id)->toBe($attacker->id)->and($battle->arena_state['attacker']['hp'])->toBe(100);
    $battle = $service->act($attacker, $battle->id, 'strike', 0);
    expect($battle->turn_player_id)->toBe($defender->id);
    expect(fn () => $service->act($attacker, $battle->id, 'strike', 1))->toThrow(ValidationException::class);
    $service->act($defender, $battle->id, 'guard', 1);
    $service->forfeit($defender, $battle->id);
    expect($battle->fresh()->winner_id)->toBe($attacker->id)
        ->and($attacker->fresh()->arena_rating)->toBe(1012)->and($defender->fresh()->arena_rating)->toBe(988)
        ->and($attacker->fresh()->health)->toBe(40)->and($attacker->fresh()->mana)->toBe(20)
        ->and($attacker->fresh()->experience)->toBe(0);
    $this->travel(61)->seconds();
    $rematch = $service->challenge($attacker->fresh(), $defender->id);
    $rematch = $service->respond($defender, $rematch->id, true);
    expect($rematch->arena_state['rated'])->toBeFalse();
    $service->forfeit($attacker, $rematch->id);
    expect($attacker->fresh()->arena_rating)->toBe(1012);
});

it('declines or expires challenges without a rating penalty and releases busy players', function (): void {
    $service = app(ArenaService::class);
    $attacker = arenaTestPlayer();
    $defender = arenaTestPlayer();
    $battle = $service->challenge($attacker, $defender->id);
    expect(fn () => $service->startNpc($attacker, Npc::first()->id))->toThrow(ValidationException::class);
    $service->respond($defender, $battle->id, false);
    expect($battle->fresh()->arena_status)->toBe('declined')->and($defender->fresh()->arena_rating)->toBe(1000);
    expect(fn () => $service->challenge($attacker, $defender->id))->toThrow(ValidationException::class);
    $this->travel(61)->seconds();
    $battle = $service->challenge($attacker, $defender->id);
    $this->travel(16)->minutes();
    $service->expireFor($attacker);
    expect($battle->fresh()->arena_status)->toBe('expired');
    $service->startNpc($attacker, Npc::first()->id);
});

it('rejects other players battle actions and private report access', function (): void {
    $service = app(ArenaService::class);
    $owner = arenaTestPlayer();
    $battle = $service->startNpc($owner, Npc::first()->id);
    $user = User::factory()->create();
    $outsider = arenaTestPlayer(['email' => $user->email]);
    $this->actingAs($user);
    Livewire::test(BattleArena::class)->call('showBattle', $battle->id)->assertNotFound();
    try {
        $service->act($outsider, $battle->id, 'strike', 0);
        test()->fail('An outsider acted on a private battle.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
    expect($battle->fresh()->arena_turn)->toBe(0);
});

it('equips only owned level appropriate items and freezes the loadout during battle', function (): void {
    $service = app(ArenaService::class);
    $player = arenaTestPlayer();
    $sword = Item::where('starter_key', 'training-sword')->first();
    $player->items()->attach($sword, ['quantity' => 1]);
    $service->equip($player, $sword->id);
    expect($player->equipment()->where('slot', 'weapon')->value('item_id'))->toBe($sword->id);
    $battle = $service->startNpc($player, Npc::first()->id);
    expect($battle->arena_state['attacker']['strength'])->toBe(18);
    expect(fn () => $service->equip($player, $sword->id))->toThrow(ValidationException::class);
    expect(fn () => $service->rest($player))->toThrow(ValidationException::class);
    $service->forfeit($player, $battle->id);
    $service->rest($player);
    expect($player->fresh()->health)->toBe(100);
});

it('ends stalled battles at the turn limit without farming rewards for draws', function (): void {
    config(['arena.max_turns' => 2]);
    $service = app(ArenaService::class);
    $a = arenaTestPlayer();
    $d = arenaTestPlayer();
    $battle = $service->challenge($a, $d->id);
    $service->respond($d, $battle->id, true);
    $service->act($a, $battle->id, 'guard', 0);
    $battle = $service->act($d, $battle->id, 'guard', 1);
    expect($battle->arena_status)->toBe('completed')->and($battle->outcome)->toBe('draw')->and($battle->winner_id)->toBeNull()
        ->and($battle->experience_gained)->toBe(0)->and($battle->rating_change)->toBe(0);
});

it('renders a full NPC encounter, equipped loadout and completed result through Livewire', function (): void {
    $user = User::factory()->create();
    $player = arenaTestPlayer(['email' => $user->email, 'strength' => 100]);
    $item = Item::where('starter_key', 'training-sword')->first();
    $player->items()->attach($item, ['quantity' => 1]);
    $npc = Npc::first();
    $npc->update(['stats' => array_merge($npc->stats, ['health' => 1])]);
    $this->actingAs($user);
    Livewire::test(BattleArena::class)
        ->call('equip', $item->id)->assertSee('Equipped')
        ->call('startNpc', $npc->id)->assertSee('Your move')->assertSee('Quick strike')
        ->call('act', 'strike', 0)->assertSee('Victory! Well fought.')->assertSee('Combat chronicle');
});

it('settles a timed out player turn as a loss instead of allowing rating avoidance', function (): void {
    $service = app(ArenaService::class);
    $a = arenaTestPlayer(['agility' => 30]);
    $d = arenaTestPlayer(['agility' => 5]);
    $battle = $service->challenge($a, $d->id);
    $service->respond($d, $battle->id, true);
    $this->travel(11)->minutes();
    $service->expireFor($a);
    expect($battle->fresh()->arena_status)->toBe('completed')
        ->and($battle->fresh()->winner_id)->toBe($d->id)
        ->and($battle->fresh()->arena_state['timeout'])->toBeTrue()
        ->and($a->fresh()->arena_rating)->toBe(988)->and($d->fresh()->arena_rating)->toBe(1012);
    $service->expireFor($a);
    expect($a->fresh()->arena_rating)->toBe(988);
});

it('handles player opt in, challenge acceptance, surrender and shrine recovery through the panel', function (): void {
    $aUser = User::factory()->create();
    $dUser = User::factory()->create();
    $a = arenaTestPlayer(['email' => $aUser->email, 'arena_opt_in' => false, 'agility' => 30, 'health' => 50]);
    $d = arenaTestPlayer(['email' => $dUser->email, 'arena_opt_in' => false, 'agility' => 5]);
    $this->actingAs($aUser);
    $attackerPanel = Livewire::test(BattleArena::class)->call('toggleOptIn')->assertHasNoErrors();
    $this->actingAs($dUser);
    Livewire::test(BattleArena::class)->call('toggleOptIn')->assertHasNoErrors();
    $this->actingAs($aUser);
    $attackerPanel->call('challenge', $d->id)->assertHasNoErrors()->assertSee('Withdraw challenge');
    $battleId = $attackerPanel->get('battleId');
    $this->actingAs($dUser);
    Livewire::test(BattleArena::class)->call('respond', $battleId, true)->assertSee('Waiting for your opponent')
        ->call('forfeit')->assertHasNoErrors()->assertSee('Defeat');
    $this->actingAs($aUser);
    $attackerPanel->call('showBattle', $battleId)->assertSee('Victory! Well fought.')
        ->call('rest')->assertHasNoErrors();
    expect($a->fresh()->health)->toBe(100)->and($d->fresh()->arena_rating)->toBe(988);
});

it('uses low health recovery and each NPC tactical pattern', function (): void {
    $service = app(ArenaService::class);
    foreach (['aggressive', 'defensive', 'balanced'] as $behavior) {
        $player = arenaTestPlayer();
        $npc = Npc::where('slug', 'practice-sentinel')->first();
        $npc->update(['behavior' => $behavior, 'stats' => array_merge($npc->stats, ['health' => 1000])]);
        $battle = $service->startNpc($player, $npc->id);
        $battle = $service->act($player, $battle->id, 'guard', 0);
        $expected = ['aggressive' => 'heavy', 'defensive' => 'guard', 'balanced' => 'strike'][$behavior];
        expect($battle->battle_log[1]['action'])->toBe($expected);
        if ($behavior === 'balanced') {
            $service->act($player, $battle->id, 'guard', 1);
            $battle = $service->act($player, $battle->id, 'guard', 2);
            expect($battle->battle_log[5]['action'])->toBe('heavy');
        }
    }
    $player = arenaTestPlayer(['strength' => 1, 'agility' => 1]);
    $npc = Npc::where('slug', 'practice-sentinel')->first();
    $npc->update(['behavior' => 'mystic', 'stats' => ['health' => 100, 'mana' => 50, 'strength' => 5, 'defense' => 100, 'agility' => 1, 'intelligence' => 8]]);
    $battle = $service->startNpc($player, $npc->id);
    $state = $battle->arena_state;
    $state['defender']['hp'] = 20;
    $battle->update(['arena_state' => $state]);
    $battle = $service->act($player, $battle->id, 'guard', 0);
    expect($battle->battle_log[1]['action'])->toBe('mend')->and($battle->arena_state['defender']['hp'])->toBeGreaterThan(20);
});
