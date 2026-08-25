<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\BrowserGame\Crafting\CraftingServiceProvider;
use Liberu\BrowserGame\Crafting\Models\CraftingDiscovery;
use Liberu\BrowserGame\Crafting\Support\CraftingManager;
use Liberu\BrowserGame\CraftingLivewire\CraftingLivewireServiceProvider;
use Liberu\BrowserGame\CraftingLivewire\Livewire\CraftingCatalog;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('discovers a scoped crafting recipe through Livewire', function (): void {
    $this->app->register(CraftingServiceProvider::class);
    $this->app->register(CraftingLivewireServiceProvider::class);
    $this->artisan('migrate');

    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->getKey()])->save();
    $recipe = app(CraftingManager::class)->define('Discovered recipe', [
        'discovery_requirements' => ['required' => true],
    ], teamId: (string) $team->getKey());

    Livewire::actingAs($user)
        ->test(CraftingCatalog::class)
        ->assertSee('Discovered recipe')
        ->call('discover', (string) $recipe->getKey())
        ->assertSee('Recipe discovered.');

    expect(CraftingDiscovery::query()->where('actor_id', (string) $user->getKey())->where('recipe_id', $recipe->getKey())->where('team_id', $team->getKey())->exists())->toBeTrue();
});
