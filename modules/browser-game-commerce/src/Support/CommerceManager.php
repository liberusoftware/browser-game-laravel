<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Support;

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

    public function createProduct(string $sku, string $name, string $currencyCode, int $price, array $delivery = [], ?int $stock = null, ?int $maxPerActor = null, array $data = []): CommerceProduct
    {
        if (trim($sku) === '' || trim($name) === '') {
            throw ValidationException::withMessages(['product' => 'SKU and name are required.']);
        }
        $this->amount($price);

        return CommerceProduct::query()->create(['id' => (string) Str::uuid(), 'sku' => $sku, 'name' => $name, 'currency_code' => strtoupper($currencyCode), 'price' => $price, 'stock' => $stock, 'max_per_actor' => $maxPerActor, 'delivery' => $delivery, 'data' => $data, 'status' => 'active']);
    }

    public function checkout(string $actorId, array $lines, ?string $idempotencyKey = null): CommerceOrder
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'At least one product is required.']);
        }

        return DB::transaction(function () use ($actorId, $lines, $idempotencyKey): CommerceOrder {
            if ($idempotencyKey !== null && ($existing = CommerceOrder::query()->where('idempotency_key', $idempotencyKey)->first())) {
                return $existing->load('lines', 'entitlements');
            }
            $products = [];
            $subtotal = 0;
            $currency = null;
            foreach ($lines as $line) {
                $product = CommerceProduct::query()->lockForUpdate()->findOrFail($line['product_id'] ?? '');
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
            $order = CommerceOrder::query()->create(['id' => (string) Str::uuid(), 'actor_id' => $actorId, 'currency_code' => $currency, 'subtotal' => $subtotal, 'total' => $subtotal, 'status' => 'pending', 'idempotency_key' => $idempotencyKey]);
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

    public function complete(CommerceOrder $order): CommerceOrder
    {
        if ($order->status === 'completed') {
            return $order;
        }
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages(['order' => 'Order cannot be completed.']);
        }
        $order->forceFill(['status' => 'completed', 'completed_at' => now()])->save();

        return $order->fresh(['lines', 'entitlements']);
    }

    public function refund(CommerceOrder $order): CommerceOrder
    {
        if ($order->status !== 'completed') {
            throw ValidationException::withMessages(['order' => 'Only completed orders can be refunded.']);
        }
        $order->update(['status' => 'refunded']);
        CommerceEntitlement::query()->where('order_id', $order->getKey())->update(['status' => 'revoked']);

        return $order->fresh(['lines', 'entitlements']);
    }

    private function amount(int $amount): void
    {
        if ($amount < 0 || $amount > 1_000_000_000) {
            throw ValidationException::withMessages(['price' => 'The amount is outside the supported range.']);
        }
    }
}
