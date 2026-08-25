<?php

use Liberu\BrowserGame\AccountsLivewire\AccountsLivewireServiceProvider;
use Liberu\BrowserGame\CharactersLivewire\CharactersLivewireServiceProvider;
use Liberu\BrowserGame\CollectionsLivewire\CollectionsLivewireServiceProvider;
use Liberu\BrowserGame\CombatLivewire\CombatLivewireServiceProvider;
use Liberu\BrowserGame\CommerceLivewire\CommerceLivewireServiceProvider;
use Liberu\BrowserGame\CompetitionLivewire\CompetitionLivewireServiceProvider;
use Liberu\BrowserGame\CraftingLivewire\CraftingLivewireServiceProvider;
use Liberu\BrowserGame\EconomyLivewire\EconomyLivewireServiceProvider;
use Liberu\BrowserGame\GameCoreLivewire\GameCoreLivewireServiceProvider;
use Liberu\BrowserGame\ItemsLivewire\ItemsLivewireServiceProvider;
use Liberu\BrowserGame\LiveOpsLivewire\LiveOpsLivewireServiceProvider;
use Liberu\BrowserGame\ModerationAndAnalyticsLivewire\ModerationAndAnalyticsLivewireServiceProvider;
use Liberu\BrowserGame\QuestsLivewire\QuestsLivewireServiceProvider;
use Liberu\BrowserGame\SocialLivewire\SocialLivewireServiceProvider;
use Liberu\BrowserGame\WorldLivewire\WorldLivewireServiceProvider;
use Livewire\Livewire;

beforeEach(function (): void {
    foreach ([
        AccountsLivewireServiceProvider::class,
        CharactersLivewireServiceProvider::class,
        CollectionsLivewireServiceProvider::class,
        CombatLivewireServiceProvider::class,
        CommerceLivewireServiceProvider::class,
        CompetitionLivewireServiceProvider::class,
        CraftingLivewireServiceProvider::class,
        EconomyLivewireServiceProvider::class,
        GameCoreLivewireServiceProvider::class,
        ItemsLivewireServiceProvider::class,
        LiveOpsLivewireServiceProvider::class,
        ModerationAndAnalyticsLivewireServiceProvider::class,
        QuestsLivewireServiceProvider::class,
        SocialLivewireServiceProvider::class,
        WorldLivewireServiceProvider::class,
    ] as $provider) {
        app()->register($provider)->boot();
    }
});

it('registers canonical browser game Livewire aliases', function (string $alias): void {
    expect(Livewire::exists($alias))->toBeTrue();
})->with([
    'module-browser-game-accounts::accounts-catalog',
    'module-browser-game-characters::character-panel',
    'module-browser-game-collections::collections-catalog',
    'module-browser-game-combat::combat-panel',
    'module-browser-game-commerce::commerce-catalog',
    'module-browser-game-competition::competition-catalog',
    'module-browser-game-crafting::crafting-catalog',
    'module-browser-game-economy::economy-catalog',
    'module-browser-game-game-core::world-overview',
    'module-browser-game-items::items-catalog',
    'module-browser-game-live-ops::live-ops-catalog',
    'module-browser-game-moderation-and-analytics::moderation-and-analytics-catalog',
    'module-browser-game-quests::quest-catalog',
    'module-browser-game-social::social-catalog',
    'module-browser-game-world::world-catalog',
]);
