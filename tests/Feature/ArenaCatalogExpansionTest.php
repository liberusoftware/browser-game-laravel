<?php

use App\Filament\App\Pages\BattleArena;
use App\Models\Item;
use App\Models\Npc;
use App\Models\Player;
use App\Models\Quest;
use App\Models\User;
use Database\Seeders\NpcSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('adds exactly fifty obtainable items and fifty opponents to the previous catalog', function (): void {
    $items = config('starter-adventure.items');
    $npcs = config('arena.npcs');
    expect($items)->toHaveCount(97)->and($npcs)->toHaveCount(60);
    $newItems = array_slice($items, 47, null, true);
    $newNpcs = array_slice($npcs, 10);
    expect($newItems)->toHaveCount(50)->and(array_unique(array_column($newNpcs, 'loot')))->toHaveCount(50);
    foreach ($newNpcs as $npc) {
        expect($newItems)->toHaveKey($npc['loot'])
            ->and($npc['level'])->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(100)
            ->and($items[$npc['loot']]['min_level'])->toBeLessThanOrEqual($npc['level']);
    }
    foreach ($newItems as $item) {
        expect($item['description'])->not->toBeEmpty()
            ->and($item['rarity'])->toBeIn(['common', 'uncommon', 'rare', 'epic', 'legendary'])
            ->and($item['sell_price'])->toBeLessThan($item['buy_price']);
        if ($item['type'] !== 'material') {
            expect($item)->toHaveKey('slot');
        }
    }
});

it('upgrades the previous NPC and item catalog without replacing edits or inventory', function (): void {
    $items = config('starter-adventure.items');
    $npcs = config('arena.npcs');
    config(['starter-adventure.items' => array_slice($items, 0, 47, true), 'arena.npcs' => array_slice($npcs, 0, 10)]);
    $this->seed(NpcSeeder::class);
    $npc = Npc::where('slug', 'practice-sentinel')->firstOrFail();
    $npc->update(['name' => 'Customized Sentinel', 'is_active' => false]);
    $item = Item::where('starter_key', 'silverleaf')->firstOrFail();
    $item->update(['description' => 'An administrator customized this item.']);
    $player = Player::factory()->create();
    $player->items()->attach($item, ['quantity' => 7]);

    config(['starter-adventure.items' => $items, 'arena.npcs' => $npcs]);
    $this->seed(NpcSeeder::class);
    $this->seed(NpcSeeder::class);
    expect(Npc::count())->toBe(60)->and(Item::count())->toBe(97)->and(Quest::count())->toBe(39)
        ->and($npc->fresh()->name)->toBe('Customized Sentinel')->and($npc->fresh()->is_active)->toBeFalse()
        ->and($item->fresh()->description)->toBe('An administrator customized this item.')
        ->and((int) $player->items()->whereKey($item->id)->first()->pivot->quantity)->toBe(7);
    foreach (Npc::with('lootItem')->get() as $opponent) {
        expect($opponent->lootItem)->not->toBeNull();
    }
});

it('paginates the hunting board without losing access to late game encounters', function (): void {
    $this->seed(NpcSeeder::class);
    $user = User::factory()->create();
    Player::factory()->create(['email' => $user->email, 'level' => 90]);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Livewire::test(BattleArena::class)
        ->assertSee('Practice Sentinel')->assertDontSee('Eclipse Crown Titan')
        ->call('gotoPage', 5, 'npcPage')->assertSee('Eclipse Crown Titan')->assertDontSee('Practice Sentinel');
});
