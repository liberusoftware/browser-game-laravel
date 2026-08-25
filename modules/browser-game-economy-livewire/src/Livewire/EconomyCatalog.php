<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyLivewire\Livewire;

use Liberu\BrowserGame\Economy\Models\EconomyListing;
use Liberu\BrowserGame\Economy\Models\EconomyWallet;
use Liberu\BrowserGame\Economy\Queries\EconomyQuery;
use Liberu\BrowserGame\Economy\Support\EconomyManager;
use Livewire\Component;

final class EconomyCatalog extends Component
{
    public string $itemKey = '';

    public string $currencyCode = '';

    public int $quantity = 1;

    public int $unitPrice = 1;

    public string $recipientId = '';

    public ?string $message = null;

    public function transfer(): void
    {
        abort_unless(auth()->check(), 403);
        $this->validate(['recipientId' => ['required', 'string', 'different:'.(string) auth()->id()], 'currencyCode' => ['required', 'string', 'max:30'], 'quantity' => ['required', 'integer', 'min:1']]);
        app(EconomyManager::class)->transfer((string) auth()->id(), $this->recipientId, $this->currencyCode, $this->quantity, 'livewire:transfer:'.auth()->id().':'.$this->recipientId.':'.$this->currencyCode.':'.$this->quantity);
        $this->message = 'Transfer completed.';
    }

    public function createListing(): void
    {
        abort_unless(auth()->check(), 403);
        $this->validate(['itemKey' => ['required', 'string', 'max:120'], 'currencyCode' => ['required', 'string', 'max:30'], 'quantity' => ['required', 'integer', 'min:1'], 'unitPrice' => ['required', 'integer', 'min:1']]);
        $team = auth()->user()?->currentTeam;
        app(EconomyManager::class)->createListing((string) auth()->id(), $this->itemKey, $this->currencyCode, $this->quantity, $this->unitPrice, [], null, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $this->dispatch('listing-created');
    }

    public function purchase(int $listingId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $listing = EconomyListing::query()->whereKey($listingId)->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()))
            ->firstOrFail();
        app(EconomyManager::class)->purchaseListing((string) auth()->id(), $listing, null, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $this->dispatch('listing-purchased');
    }

    public function cancel(int $listingId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $listing = EconomyListing::query()->whereKey($listingId)
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()))
            ->firstOrFail();
        app(EconomyManager::class)->cancelListing((string) auth()->id(), $listing, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $this->dispatch('listing-cancelled');
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $economy = app(EconomyQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->latest()->limit(25)->get();

        $wallets = EconomyWallet::query()->where('actor_id', (string) auth()->id())->get();
        $listings = EconomyListing::query()->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()))
            ->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-economy-livewire::economy-catalog', compact('economy', 'wallets', 'listings'));
    }
}
