<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceLivewire\Livewire;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Commerce\Models\CommerceOrder;
use Liberu\BrowserGame\Commerce\Models\CommerceProduct;
use Liberu\BrowserGame\Commerce\Queries\CommerceQuery;
use Liberu\BrowserGame\Commerce\Support\CommerceManager;
use Livewire\Component;

final class CommerceCatalog extends Component
{
    public string $productId = '';

    public int $quantity = 1;

    public ?string $message = null;

    public ?string $error = null;

    public function checkout(string $productId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);
        $product = CommerceProduct::query()->whereKey($productId)->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $team->getAttribute('tenant_id')))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team->getKey()))
            ->firstOrFail();

        try {
            app(CommerceManager::class)->checkout((string) auth()->id(), [['product_id' => $product->getKey(), 'quantity' => $this->quantity]], (string) Str::uuid(), $team->getAttribute('tenant_id'), (string) $team->getKey());
            $this->message = 'Order created.';
            $this->error = null;
        } catch (ValidationException $exception) {
            $this->error = (string) collect($exception->errors())->flatten()->first();
        }
    }

    public function complete(string $orderId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);
        $order = $this->ownedOrder($orderId, $team);
        app(CommerceManager::class)->complete($order, (string) auth()->id(), $team->getAttribute('tenant_id'), (string) $team->getKey());
        $this->message = 'Order completed.';
        $this->error = null;
    }

    public function refund(string $orderId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);
        $order = $this->ownedOrder($orderId, $team);
        app(CommerceManager::class)->refund($order, (string) auth()->id(), $team->getAttribute('tenant_id'), (string) $team->getKey());
        $this->message = 'Order refunded.';
        $this->error = null;
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $commerce = app(CommerceQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->latest()->limit(25)->get();
        $products = CommerceProduct::query()->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()))
            ->latest()->limit(25)->get();
        $orders = auth()->check() ? CommerceOrder::query()->where('actor_id', (string) auth()->id())
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $team?->getKey()))
            ->latest()->limit(25)->get() : collect();

        return resolve('view')->make('browser-game-commerce-livewire::commerce-catalog', ['commerce' => $commerce, 'products' => $products, 'orders' => $orders]);
    }

    private function ownedOrder(string $orderId, mixed $team): CommerceOrder
    {
        return CommerceOrder::query()->whereKey($orderId)->where('actor_id', (string) auth()->id())
            ->where('tenant_id', $team->getAttribute('tenant_id'))
            ->where('team_id', $team->getKey())
            ->firstOrFail();
    }
}
