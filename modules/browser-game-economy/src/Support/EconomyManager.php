<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Economy\Events\EconomyDefined;
use Liberu\BrowserGame\Economy\Events\EconomyFeeCharged;
use Liberu\BrowserGame\Economy\Events\EconomyListingSold;
use Liberu\BrowserGame\Economy\Events\EconomyTransactionRecorded;
use Liberu\BrowserGame\Economy\Models\EconomyLedgerEntry;
use Liberu\BrowserGame\Economy\Models\EconomyListing;
use Liberu\BrowserGame\Economy\Models\EconomyRecord;
use Liberu\BrowserGame\Economy\Models\EconomyVendor;
use Liberu\BrowserGame\Economy\Models\EconomyVendorOffer;
use Liberu\BrowserGame\Economy\Models\EconomyWallet;

final class EconomyManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): EconomyRecord
    {
        $this->required($name, 'name');
        $code = strtolower(trim((string) ($data['code'] ?? Str::slug($name, '_'))));
        $this->required($code, 'code');
        if (EconomyRecord::query()->where('code', $code)->where('kind', 'currency')->exists()) {
            throw ValidationException::withMessages(['code' => 'The currency code is already defined.']);
        }
        $record = DB::transaction(fn (): EconomyRecord => EconomyRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'code' => $code,
            'kind' => $data['kind'] ?? 'currency',
            'precision' => max(0, min(6, (int) ($data['precision'] ?? 0))),
            'fee_basis_points' => max(0, min(10000, (int) ($data['fee_basis_points'] ?? 0))),
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        EconomyDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function credit(string $actorId, string $currencyCode, int $amount, string $source = 'faucet', ?string $idempotencyKey = null, array $metadata = []): EconomyLedgerEntry
    {
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
        }

        return $this->adjust($actorId, $currencyCode, $amount, 'credit', $source, $idempotencyKey, $metadata);
    }

    public function debit(string $actorId, string $currencyCode, int $amount, string $source = 'sink', ?string $idempotencyKey = null, array $metadata = []): EconomyLedgerEntry
    {
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
        }

        return $this->adjust($actorId, $currencyCode, -$amount, 'debit', $source, $idempotencyKey, $metadata);
    }

    public function transfer(string $senderId, string $recipientId, string $currencyCode, int $amount, ?string $idempotencyKey = null): array
    {
        if ($senderId === $recipientId) {
            throw ValidationException::withMessages(['recipient_id' => 'An actor cannot transfer to itself.']);
        }

        return DB::transaction(function () use ($senderId, $recipientId, $currencyCode, $amount, $idempotencyKey): array {
            $debit = $this->debit($senderId, $currencyCode, $amount, 'trade', $idempotencyKey);
            $credit = $this->credit($recipientId, $currencyCode, $amount, 'trade', $idempotencyKey === null ? null : $idempotencyKey.':recipient');

            return ['debit' => $debit, 'credit' => $credit];
        });
    }

    public function createVendor(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): EconomyVendor
    {
        $this->required($name, 'name');

        return EconomyVendor::query()->create(['name' => $name, 'data' => $data, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'status' => 'active']);
    }

    public function addOffer(EconomyVendor $vendor, string $itemKey, string $currencyCode, int $unitPrice, ?int $stock = null, ?int $maxPerActor = null, ?string $tenantId = null, ?string $teamId = null): EconomyVendorOffer
    {
        $this->assertScope($vendor, $tenantId, $teamId);
        $this->required($itemKey, 'item_key');
        $this->currency($currencyCode);
        if ($unitPrice < 1 || ($stock !== null && $stock < 0)) {
            throw ValidationException::withMessages(['offer' => 'Offer price or stock is invalid.']);
        }

        return $vendor->offers()->updateOrCreate(
            ['item_key' => $itemKey],
            ['currency_code' => strtolower(trim($currencyCode)), 'unit_price' => $unitPrice, 'stock' => $stock, 'max_per_actor' => $maxPerActor],
        );
    }

    public function purchaseOffer(string $actorId, EconomyVendorOffer $offer, int $quantity = 1, ?string $tenantId = null, ?string $teamId = null): EconomyVendorOffer
    {
        $this->assertScope($offer->vendor()->firstOrFail(), $tenantId, $teamId);
        if ($quantity < 1 || ($offer->stock !== null && $offer->stock < $quantity)) {
            throw ValidationException::withMessages(['quantity' => 'The requested vendor quantity is unavailable.']);
        }

        return DB::transaction(function () use ($actorId, $offer, $quantity, $tenantId, $teamId): EconomyVendorOffer {
            $offer = EconomyVendorOffer::query()->lockForUpdate()->findOrFail($offer->getKey());
            $this->assertScope($offer->vendor()->firstOrFail(), $tenantId, $teamId);
            if ($quantity < 1 || ($offer->stock !== null && $offer->stock < $quantity)) {
                throw ValidationException::withMessages(['quantity' => 'The requested vendor quantity is unavailable.']);
            }
            $purchases = (array) ($offer->data['purchases'] ?? []);
            $purchased = (int) ($purchases[$actorId] ?? 0);
            if ($offer->max_per_actor !== null && $purchased + $quantity > (int) $offer->max_per_actor) {
                throw ValidationException::withMessages(['quantity' => 'The actor purchase limit for this offer has been reached.']);
            }
            $this->debit($actorId, $offer->currency_code, $offer->unit_price * $quantity, 'vendor');
            if ($offer->stock !== null) {
                $offer->decrement('stock', $quantity);
            }
            $purchases[$actorId] = $purchased + $quantity;
            $offer->data = array_merge((array) $offer->data, ['purchases' => $purchases]);
            $offer->save();

            return $offer->refresh();
        });
    }

    public function createListing(string $sellerId, string $itemKey, string $currencyCode, int $quantity, int $unitPrice, array $assetReference = [], ?string $idempotencyKey = null, ?string $tenantId = null, ?string $teamId = null): EconomyListing
    {
        $this->required($sellerId, 'seller_id');
        $this->required($itemKey, 'item_key');
        $this->currency($currencyCode);
        if ($quantity < 1 || $unitPrice < 1) {
            throw ValidationException::withMessages(['listing' => 'Listing quantity and price must be positive.']);
        }
        if ($idempotencyKey !== null && ($existing = EconomyListing::query()->where('idempotency_key', $idempotencyKey)->first()) !== null) {
            if ($existing->seller_id !== $sellerId || $existing->item_key !== $itemKey || $existing->currency_code !== strtolower(trim($currencyCode)) || (int) $existing->quantity !== $quantity || (int) $existing->unit_price !== $unitPrice || $existing->tenant_id !== $tenantId || (string) $existing->team_id !== (string) $teamId) {
                throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another listing.']);
            }

            return $existing;
        }

        $currency = $this->currency($currencyCode);
        $total = $quantity * $unitPrice;
        $fee = intdiv($total * (int) $currency->fee_basis_points, 10000);

        return EconomyListing::query()->create([
            'seller_id' => $sellerId, 'item_key' => $itemKey, 'currency_code' => strtolower(trim($currencyCode)),
            'quantity' => $quantity, 'unit_price' => $unitPrice, 'fee' => $fee,
            'asset_reference' => $assetReference, 'idempotency_key' => $idempotencyKey, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'status' => 'active',
        ]);
    }

    public function purchaseListing(string $buyerId, EconomyListing $listing, ?string $idempotencyKey = null, ?string $tenantId = null, ?string $teamId = null): EconomyListing
    {
        $this->assertScope($listing, $tenantId, $teamId);

        return DB::transaction(function () use ($buyerId, $listing, $idempotencyKey, $tenantId, $teamId): EconomyListing {
            $listing = EconomyListing::query()->lockForUpdate()->findOrFail($listing->getKey());
            $this->assertScope($listing, $tenantId, $teamId);
            if ($listing->status !== 'active') {
                throw ValidationException::withMessages(['listing' => 'The listing is no longer active.']);
            }
            if ($listing->seller_id === $buyerId) {
                throw ValidationException::withMessages(['listing' => 'You cannot purchase your own listing.']);
            }
            $total = (int) $listing->unit_price * (int) $listing->quantity;
            $this->debit($buyerId, $listing->currency_code, $total, 'auction', $idempotencyKey);
            $this->credit($listing->seller_id, $listing->currency_code, $total - (int) $listing->fee, 'auction', $idempotencyKey === null ? null : $idempotencyKey.':seller');
            $listing->update(['buyer_id' => $buyerId, 'status' => 'sold', 'sold_at' => now()]);
            EconomyListingSold::dispatch((int) $listing->getKey(), $buyerId, $listing->seller_id, $total);
            if ((int) $listing->fee > 0) {
                EconomyFeeCharged::dispatch((int) $listing->getKey(), $listing->currency_code, (int) $listing->fee, 'auction');
            }

            return $listing->refresh();
        });
    }

    public function cancelListing(string $sellerId, EconomyListing $listing, ?string $tenantId = null, ?string $teamId = null): EconomyListing
    {
        $listing = EconomyListing::query()->whereKey($listing->getKey())->firstOrFail();
        $this->assertScope($listing, $tenantId, $teamId);
        if ($listing->seller_id !== $sellerId || $listing->status !== 'active') {
            throw ValidationException::withMessages(['listing' => 'The listing cannot be cancelled.']);
        }
        $listing->update(['status' => 'cancelled']);

        return $listing->refresh();
    }

    private function adjust(string $actorId, string $currencyCode, int $amount, string $entryType, string $source, ?string $idempotencyKey, array $metadata): EconomyLedgerEntry
    {
        $this->required($actorId, 'actor_id');
        $currencyCode = strtolower(trim($currencyCode));
        $this->currency($currencyCode);
        $this->assertAmount(abs($amount));

        return DB::transaction(function () use ($actorId, $currencyCode, $amount, $entryType, $source, $idempotencyKey, $metadata): EconomyLedgerEntry {
            if ($idempotencyKey !== null && ($existing = EconomyLedgerEntry::query()->where('idempotency_key', $idempotencyKey)->first()) !== null) {
                if ($existing->actor_id !== $actorId || $existing->currency_code !== $currencyCode || (int) $existing->amount !== $amount || $existing->entry_type !== $entryType) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another ledger operation.']);
                }

                return $existing;
            }
            $wallet = EconomyWallet::query()->lockForUpdate()->firstOrCreate(
                ['actor_id' => $actorId, 'currency_code' => $currencyCode],
                ['balance' => 0],
            );
            if ($amount < 0 && $wallet->balance < abs($amount)) {
                throw ValidationException::withMessages(['balance' => 'Insufficient currency balance.']);
            }
            $balance = (int) $wallet->balance + $amount;
            $wallet->update(['balance' => $balance]);
            $entry = EconomyLedgerEntry::query()->create([
                'id' => (string) Str::uuid(), 'actor_id' => $actorId, 'currency_code' => $currencyCode,
                'amount' => $amount, 'balance_after' => $balance, 'entry_type' => $entryType,
                'source' => $source, 'idempotency_key' => $idempotencyKey, 'metadata' => $metadata,
            ]);
            EconomyTransactionRecorded::dispatch($actorId, $currencyCode, $amount, $entryType);

            return $entry;
        });
    }

    private function currency(string $code): EconomyRecord
    {
        $record = EconomyRecord::query()->where('code', strtolower($code))->where('kind', 'currency')->where('status', 'active')->first();
        if ($record === null) {
            throw ValidationException::withMessages(['currency_code' => 'The currency is unavailable.']);
        }

        return $record;
    }

    private function assertAmount(int $amount): void
    {
        if ($amount < 1 || $amount > (int) config('browser-game.economy.max_transaction', 1000000000)) {
            throw ValidationException::withMessages(['amount' => 'The transaction amount is outside the allowed range.']);
        }
    }

    private function required(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw ValidationException::withMessages([$field => 'A value is required.']);
        }
    }

    private function assertScope(object $record, ?string $tenantId, ?string $teamId): void
    {
        if (($record->tenant_id !== null && (string) $record->tenant_id !== (string) $tenantId) || ($record->team_id !== null && (string) $record->team_id !== (string) $teamId)) {
            throw ValidationException::withMessages(['scope' => 'The economy resource is not available in this scope.']);
        }
    }
}
