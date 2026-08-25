<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingLivewire\Livewire;

use Liberu\BrowserGame\Crafting\Models\CraftingQueue;
use Liberu\BrowserGame\Crafting\Queries\CraftingQuery;
use Liberu\BrowserGame\Crafting\Support\CraftingManager;
use Livewire\Component;

final class CraftingCatalog extends Component
{
    public int $quantity = 1;

    public int $quality = 100;

    public ?string $statusMessage = null;

    public function queue(string $recipeId): void
    {
        abort_unless(auth()->check(), 403);
        $this->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000'], 'quality' => ['required', 'integer', 'min:0', 'max:100']]);
        $team = $this->team();
        $recipe = app(CraftingQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($recipeId)->where('status', 'active')->firstOrFail();
        app(CraftingManager::class)->queueCraft((string) auth()->id(), $recipe, $this->quantity, $this->quality, null, 1, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $this->statusMessage = 'Crafting queued.';
        $this->dispatch('crafting-queued');
    }

    public function complete(string $queueId): void
    {
        abort_unless(auth()->check(), 403);
        $queue = CraftingQueue::query()->whereKey($queueId)->where('actor_id', (string) auth()->id())->firstOrFail();
        app(CraftingManager::class)->complete($queue);
        $this->statusMessage = 'Crafting completed.';
        $this->dispatch('crafting-completed');
    }

    public function cancel(string $queueId): void
    {
        abort_unless(auth()->check(), 403);
        $queue = CraftingQueue::query()->whereKey($queueId)->where('actor_id', (string) auth()->id())->firstOrFail();
        app(CraftingManager::class)->cancel($queue);
        $this->statusMessage = 'Crafting cancelled and materials refunded.';
        $this->dispatch('crafting-cancelled');
    }

    public function salvage(string $queueId): void
    {
        abort_unless(auth()->check(), 403);
        $queue = CraftingQueue::query()->whereKey($queueId)->where('actor_id', (string) auth()->id())->firstOrFail();
        app(CraftingManager::class)->salvage($queue);
        $this->statusMessage = 'Crafting output salvaged.';
        $this->dispatch('crafting-salvaged');
    }

    public function render(): mixed
    {
        $team = $this->team();
        $crafting = app(CraftingQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->latest()->limit(25)->get();
        $queues = auth()->check()
            ? CraftingQueue::query()->with('recipe')->where('actor_id', (string) auth()->id())->latest()->limit(25)->get()
            : collect();

        return resolve('view')->make('browser-game-crafting-livewire::crafting-catalog', ['crafting' => $crafting, 'queues' => $queues]);
    }

    private function team(): mixed
    {
        $user = auth()->user();

        return is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
    }
}
