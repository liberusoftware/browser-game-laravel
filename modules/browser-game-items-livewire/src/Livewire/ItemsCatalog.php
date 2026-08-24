<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsLivewire\Livewire;

use Liberu\BrowserGame\Items\Models\InventoryEntry;
use Liberu\BrowserGame\Items\Models\ItemsRecord;
use Liberu\BrowserGame\Items\Queries\ItemsQuery;
use Liberu\BrowserGame\Items\Support\ItemsManager;
use Livewire\Component;

final class ItemsCatalog extends Component
{
    public function add(string $itemId): void
    {
        $item = ItemsRecord::query()->whereKey($itemId)->where('status', 'active')->firstOrFail();
        app(ItemsManager::class)->addToInventory((string) auth()->id(), $item);
        $this->dispatch('inventory-updated');
    }

    public function remove(string $itemId): void
    {
        $item = ItemsRecord::query()->whereKey($itemId)->where('status', 'active')->firstOrFail();
        app(ItemsManager::class)->removeFromInventory((string) auth()->id(), $item);
        $this->dispatch('inventory-updated');
    }

    public function equip(int $entryId): void
    {
        app(ItemsManager::class)->equip((string) auth()->id(), InventoryEntry::query()->findOrFail($entryId));
        $this->dispatch('inventory-updated');
    }

    public function bind(int $entryId): void
    {
        app(ItemsManager::class)->bind((string) auth()->id(), InventoryEntry::query()->findOrFail($entryId));
        $this->dispatch('inventory-updated');
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $items = app(ItemsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-items-livewire::items-catalog', ['items' => $items]);
    }
}
