<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsLivewire\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Items\Models\InventoryEntry;
use Liberu\BrowserGame\Items\Queries\ItemsQuery;
use Liberu\BrowserGame\Items\Support\ItemsManager;
use Livewire\Component;

final class ItemsCatalog extends Component
{
    public ?string $message = null;

    public function add(string $itemId): void
    {
        abort_unless(auth()->check(), 403);
        $item = $this->visibleItems()->whereKey($itemId)->where('status', 'active')->firstOrFail();
        app(ItemsManager::class)->addToInventory((string) auth()->id(), $item, tenantId: $this->tenantId(), teamId: $this->teamId());
        $this->dispatch('inventory-updated');
    }

    public function remove(string $itemId): void
    {
        abort_unless(auth()->check(), 403);
        $item = $this->visibleItems()->whereKey($itemId)->where('status', 'active')->firstOrFail();
        app(ItemsManager::class)->removeFromInventory((string) auth()->id(), $item, tenantId: $this->tenantId(), teamId: $this->teamId());
        $this->dispatch('inventory-updated');
    }

    public function equip(int $entryId): void
    {
        abort_unless(auth()->check(), 403);
        app(ItemsManager::class)->equip((string) auth()->id(), InventoryEntry::query()->where('player_id', (string) auth()->id())->whereKey($entryId)->firstOrFail(), tenantId: $this->tenantId(), teamId: $this->teamId());
        $this->dispatch('inventory-updated');
    }

    public function bind(int $entryId): void
    {
        abort_unless(auth()->check(), 403);
        app(ItemsManager::class)->bind((string) auth()->id(), InventoryEntry::query()->where('player_id', (string) auth()->id())->whereKey($entryId)->firstOrFail(), tenantId: $this->tenantId(), teamId: $this->teamId());
        $this->dispatch('inventory-updated');
    }

    public function unequip(int $entryId): void
    {
        abort_unless(auth()->check(), 403);
        app(ItemsManager::class)->unequip((string) auth()->id(), $this->ownedEntry($entryId), $this->tenantId(), $this->teamId());
        $this->message = 'Item unequipped.';
        $this->dispatch('inventory-updated');
    }

    public function adjustDurability(int $entryId, int $delta): void
    {
        abort_unless(auth()->check(), 403);
        app(ItemsManager::class)->adjustDurability((string) auth()->id(), $this->ownedEntry($entryId), $delta, $this->tenantId(), $this->teamId());
        $this->message = 'Item durability updated.';
        $this->dispatch('inventory-updated');
    }

    public function setProvenance(int $entryId, array $provenance): void
    {
        abort_unless(auth()->check(), 403);
        app(ItemsManager::class)->setProvenance((string) auth()->id(), $this->ownedEntry($entryId), $provenance, $this->tenantId(), $this->teamId());
        $this->message = 'Item provenance updated.';
        $this->dispatch('inventory-updated');
    }

    public function putInContainer(int $entryId, int $containerId): void
    {
        abort_unless(auth()->check(), 403);
        app(ItemsManager::class)->putInContainer((string) auth()->id(), $this->ownedEntry($entryId), $this->ownedEntry($containerId), $this->tenantId(), $this->teamId());
        $this->message = 'Item placed in container.';
        $this->dispatch('inventory-updated');
    }

    public function render(): mixed
    {
        $items = $this->visibleItems()->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-items-livewire::items-catalog', ['items' => $items]);
    }

    private function visibleItems(): Builder
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return app(ItemsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
    }

    private function ownedEntry(int $entryId): InventoryEntry
    {
        return InventoryEntry::query()->where('player_id', (string) auth()->id())->whereKey($entryId)->firstOrFail();
    }

    private function tenantId(): ?string
    {
        return auth()->user()?->currentTeam?->getAttribute('tenant_id');
    }

    private function teamId(): ?string
    {
        $team = auth()->user()?->currentTeam;

        return $team?->getKey() === null ? null : (string) $team->getKey();
    }
}
