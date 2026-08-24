<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceLivewire\Livewire;

use Illuminate\Support\Str;
use Liberu\BrowserGame\Commerce\Models\CommerceProduct;
use Liberu\BrowserGame\Commerce\Queries\CommerceQuery;
use Liberu\BrowserGame\Commerce\Support\CommerceManager;
use Livewire\Component;

final class CommerceCatalog extends Component
{
    public string $productId = '';

    public int $quantity = 1;

    public ?string $message = null;

    public function checkout(string $productId): void
    {
        abort_unless(auth()->check(), 403);
        app(CommerceManager::class)->checkout((string) auth()->id(), [['product_id' => $productId, 'quantity' => $this->quantity]], (string) Str::uuid());
        $this->message = 'Order created.';
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $commerce = app(CommerceQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();
        $products = CommerceProduct::query()->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-commerce-livewire::commerce-catalog', ['commerce' => $commerce, 'products' => $products]);
    }
}
