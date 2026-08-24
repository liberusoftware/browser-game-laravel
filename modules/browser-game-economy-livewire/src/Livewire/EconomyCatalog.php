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

    public function createListing(): void
    {
        $this->validate(['itemKey' => ['required', 'string', 'max:120'], 'currencyCode' => ['required', 'string', 'max:30'], 'quantity' => ['required', 'integer', 'min:1'], 'unitPrice' => ['required', 'integer', 'min:1']]);
        app(EconomyManager::class)->createListing((string) auth()->id(), $this->itemKey, $this->currencyCode, $this->quantity, $this->unitPrice);
        $this->dispatch('listing-created');
    }

    public function purchase(int $listingId): void
    {
        $listing = EconomyListing::query()->whereKey($listingId)->where('status', 'active')->firstOrFail();
        app(EconomyManager::class)->purchaseListing((string) auth()->id(), $listing);
        $this->dispatch('listing-purchased');
    }

    public function cancel(int $listingId): void
    {
        $listing = EconomyListing::query()->whereKey($listingId)->firstOrFail();
        app(EconomyManager::class)->cancelListing((string) auth()->id(), $listing);
        $this->dispatch('listing-cancelled');
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $economy = app(EconomyQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        $wallets = EconomyWallet::query()->where('actor_id', (string) auth()->id())->get();
        $listings = EconomyListing::query()->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-economy-livewire::economy-catalog', compact('economy', 'wallets', 'listings'));
    }
}
