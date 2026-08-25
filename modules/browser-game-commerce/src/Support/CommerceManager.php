<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Commerce\Models\CommerceEntitlement;
use Liberu\BrowserGame\Commerce\Models\CommerceOrder;
use Liberu\BrowserGame\Commerce\Models\CommerceProduct;
use Liberu\BrowserGame\Commerce\Models\CommerceRecord;

final class CommerceManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CommerceRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): CommerceRecord => CommerceRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));

        return $record;
    }

    public function createProduct(string $sku, string $name, string $currencyCode, int $price, array $delivery = [], ?int $stock = null, ?int $maxPerActor = null, array $data = [], ?string $tenantId = null, ?string $teamId = null): CommerceProduct
    {
        if (trim($sku) === '' || trim($name) === '' || trim($currencyCode) !== strtoupper(trim($currencyCode)) || strlen(trim($currencyCode)) !== 3) {
            throw ValidationException::withMessages(['product' => 'SKU and name are required.']);
        }
        if (($stock !== null && $stock < 0) || ($maxPerActor !== null && $maxPerActor < 1)) {
            throw ValidationException::withMessages(['stock' => 'Stock and purchase limits are invalid.']);
        }
        $this->amount($price);

        return CommerceProduct::query()->create(['id' => (string) Str::uuid(), 'sku' => $sku, 'name' => $name, 'currency_code' => strtoupper($currencyCode), 'price' => $price, 'stock' => $stock, 'max_per_actor' => $maxPerActor, 'delivery' => $delivery, 'data' => $data, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'status' => 'active']);
    }

    public function checkout(string $actorId, array $lines, ?string $idempotencyKey = null, ?string $tenantId = null, ?string $teamId = null): CommerceOrder
    {
        if (trim($actorId) === '') {
            throw ValidationException::withMessages(['actor_id' => 'An actor is required.']);
        }
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'At least one product is required.']);
        }

        return DB::transaction(function () use ($actorId, $lines, $idempotencyKey, $tenantId, $teamId): CommerceOrder {
            if ($idempotencyKey !== null && ($existing = CommerceOrder::query()->where('actor_id', $actorId)->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                $this->assertScope($existing, $tenantId, $teamId);
                $existing->load('lines', 'entitlements');
                $requestedLines = collect($lines)->map(fn (array $line): string => (string) ($line['product_id'] ?? '').':'.((int) ($line['quantity'] ?? 0)))->sort()->values()->all();
                $storedLines = $existing->lines->map(fn ($line): string => (string) $line->product_id.':'.((int) $line->quantity))->sort()->values()->all();
                if ($requestedLines !== $storedLines) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another checkout.']);
                }

                return $existing;
            }
            $products = [];
            $subtotal = 0;
            $currency = null;
            foreach ($lines as $line) {
                $product = $this->visibleProducts($tenantId, $teamId)->lockForUpdate()->findOrFail($line['product_id'] ?? '');
                $quantity = (int) ($line['quantity'] ?? 0);
                if ($product->status !== 'active' || $quantity < 1 || ($product->stock !== null && $product->stock < $quantity)) {
                    throw ValidationException::withMessages(['product' => 'Product is unavailable.']);
                }
                if ($product->max_per_actor !== null) {
                    $purchased = (int) DB::table('browser_game_commerce_order_lines')->join('browser_game_commerce_orders', 'browser_game_commerce_orders.id', '=', 'browser_game_commerce_order_lines.order_id')->where('browser_game_commerce_orders.actor_id', $actorId)->where('browser_game_commerce_orders.status', 'completed')->where('browser_game_commerce_order_lines.product_id', $product->getKey())->sum('browser_game_commerce_order_lines.quantity');
                    if ($purchased + $quantity > $product->max_per_actor) {
                        throw ValidationException::withMessages(['quantity' => 'Purchase limit exceeded.']);
                    }
                }
                $currency ??= $product->currency_code;
                if ($currency !== $product->currency_code) {
                    throw ValidationException::withMessages(['currency' => 'All products must use one currency.']);
                }
                $total = $product->price * $quantity;
                $subtotal += $total;
                $products[] = [$product, $quantity, $total];
            }
            $order = CommerceOrder::query()->create(['id' => (string) Str::uuid(), 'actor_id' => $actorId, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'currency_code' => $currency, 'subtotal' => $subtotal, 'total' => $subtotal, 'status' => 'pending', 'idempotency_key' => $idempotencyKey]);
            foreach ($products as [$product, $quantity, $total]) {
                if ($product->stock !== null) {
                    $product->decrement('stock', $quantity);
                }
                $order->lines()->create(['product_id' => $product->getKey(), 'quantity' => $quantity, 'unit_price' => $product->price, 'line_total' => $total, 'delivery' => $product->delivery]);
                foreach ($product->delivery ?? [] as $key => $value) {
                    $order->entitlements()->create(['actor_id' => $actorId, 'product_id' => $product->getKey(), 'delivery_key' => (string) $key, 'quantity' => is_numeric($value) ? (int) $value * $quantity : $quantity, 'data' => ['value' => $value]]);
                }
            }

            return $order->load('lines', 'entitlements');
        });
    }

    public function complete(CommerceOrder $order, ?string $actorId = null, ?string $tenantId = null, ?string $teamId = null): CommerceOrder
    {
        return DB::transaction(function () use ($order, $actorId, $tenantId, $teamId): CommerceOrder {
            $order = CommerceOrder::query()->lockForUpdate()->findOrFail($order->getKey());
            $this->assertOrderActor($order, $actorId);
            $this->assertScope($order, $tenantId, $teamId);
            if ($order->status === 'completed') {
                return $order->fresh(['lines', 'entitlements']);
            }
            if ($order->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Order cannot be completed.']);
            }
            $order->forceFill(['status' => 'completed', 'completed_at' => now()])->save();

            return $order->fresh(['lines', 'entitlements']);
        });
    }

    public function refund(CommerceOrder $order, ?string $actorId = null, ?string $tenantId = null, ?string $teamId = null): CommerceOrder
    {
        return DB::transaction(function () use ($order, $actorId, $tenantId, $teamId): CommerceOrder {
            $order = CommerceOrder::query()->lockForUpdate()->findOrFail($order->getKey());
            $this->assertOrderActor($order, $actorId);
            $this->assertScope($order, $tenantId, $teamId);
            if ($order->status === 'refunded') {
                return $order->fresh(['lines', 'entitlements']);
            }
            if ($order->status !== 'completed') {
                throw ValidationException::withMessages(['order' => 'Only completed orders can be refunded.']);
            }
            $order->update(['status' => 'refunded']);
            CommerceEntitlement::query()->where('order_id', $order->getKey())->update(['status' => 'revoked']);

            return $order->fresh(['lines', 'entitlements']);
        });
    }

    private function amount(int $amount): void
    {
        if ($amount < 0 || $amount > 1_000_000_000) {
            throw ValidationException::withMessages(['price' => 'The amount is outside the supported range.']);
        }
    }

    private function assertOrderActor(CommerceOrder $order, ?string $actorId): void
    {
        if ($actorId !== null && (string) $order->actor_id !== (string) $actorId) {
            throw ValidationException::withMessages(['order' => 'The order is not available to this actor.']);
        }
    }

    private function visibleProducts(?string $tenantId, ?string $teamId): Builder
    {
        return CommerceProduct::query()
            ->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $teamId));
    }

    private function assertScope(CommerceOrder $order, ?string $tenantId, ?string $teamId): void
    {
        if (($order->tenant_id !== null && (string) $order->tenant_id !== (string) $tenantId) || ($order->team_id !== null && (string) $order->team_id !== (string) $teamId)) {
            throw ValidationException::withMessages(['order' => 'The order is not available in this scope.']);
        }
    }
}
