<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingLivewire\Livewire;

use Liberu\BrowserGame\Crafting\Models\CraftingQueue;
use Liberu\BrowserGame\Crafting\Models\CraftingRecord;
use Liberu\BrowserGame\Crafting\Queries\CraftingQuery;
use Liberu\BrowserGame\Crafting\Support\CraftingManager;
use Livewire\Component;

final class CraftingCatalog extends Component
{
    public int $quantity = 1;

    public int $quality = 100;

    public function queue(string $recipeId): void
    {
        $this->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000'], 'quality' => ['required', 'integer', 'min:0', 'max:100']]);
        $recipe = CraftingRecord::query()->whereKey($recipeId)->where('status', 'active')->firstOrFail();
        app(CraftingManager::class)->queueCraft((string) auth()->id(), $recipe, $this->quantity, $this->quality);
        $this->dispatch('crafting-queued');
    }

    public function complete(string $queueId): void
    {
        $queue = CraftingQueue::query()->whereKey($queueId)->where('actor_id', (string) auth()->id())->firstOrFail();
        app(CraftingManager::class)->complete($queue);
        $this->dispatch('crafting-completed');
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $crafting = app(CraftingQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-crafting-livewire::crafting-catalog', ['crafting' => $crafting]);
    }
}
