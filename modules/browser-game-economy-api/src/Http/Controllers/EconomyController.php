<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Economy\Models\EconomyListing;
use Liberu\BrowserGame\Economy\Models\EconomyRecord;
use Liberu\BrowserGame\Economy\Models\EconomyVendor;
use Liberu\BrowserGame\Economy\Models\EconomyVendorOffer;
use Liberu\BrowserGame\Economy\Models\EconomyWallet;
use Liberu\BrowserGame\Economy\Queries\EconomyQuery;
use Liberu\BrowserGame\Economy\Support\EconomyManager;

final class EconomyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $items = app(EconomyQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

        return response()->json(['data' => $items->through(fn (EconomyRecord $item): array => $this->resource($item))]);
    }

    public function show(Request $request, EconomyRecord $economy): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        $economy = app(EconomyQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())
            ->whereKey($economy->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($economy)]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $wallets = EconomyWallet::query()->where('actor_id', (string) $request->user()->getAuthIdentifier())->get();

        return response()->json(['data' => $wallets->map(fn (EconomyWallet $wallet): array => $this->walletResource($wallet))->values()]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate(['recipient_id' => ['required', 'string', 'max:120'], 'currency_code' => ['required', 'string', 'max:30'], 'amount' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['nullable', 'string', 'max:120']]);
        $result = app(EconomyManager::class)->transfer((string) $request->user()->getAuthIdentifier(), $data['recipient_id'], $data['currency_code'], (int) $data['amount'], $data['idempotency_key'] ?? null);

        return response()->json(['data' => ['debit' => $this->walletResource($result['debit']), 'credit' => $this->walletResource($result['credit'])]]);
    }

    public function credit(Request $request): JsonResponse
    {
        $data = $request->validate(['currency_code' => ['required', 'string', 'max:30'], 'amount' => ['required', 'integer', 'min:1'], 'source' => ['nullable', 'string', 'max:80'], 'idempotency_key' => ['nullable', 'string', 'max:120'], 'metadata' => ['array']]);
        $entry = app(EconomyManager::class)->credit((string) $request->user()->getAuthIdentifier(), $data['currency_code'], (int) $data['amount'], $data['source'] ?? 'faucet', $data['idempotency_key'] ?? null, $data['metadata'] ?? []);

        return response()->json(['data' => $this->ledgerResource($entry)], 201);
    }

    public function debit(Request $request): JsonResponse
    {
        $data = $request->validate(['currency_code' => ['required', 'string', 'max:30'], 'amount' => ['required', 'integer', 'min:1'], 'source' => ['nullable', 'string', 'max:80'], 'idempotency_key' => ['nullable', 'string', 'max:120'], 'metadata' => ['array']]);
        $entry = app(EconomyManager::class)->debit((string) $request->user()->getAuthIdentifier(), $data['currency_code'], (int) $data['amount'], $data['source'] ?? 'sink', $data['idempotency_key'] ?? null, $data['metadata'] ?? []);

        return response()->json(['data' => $this->ledgerResource($entry)], 201);
    }

    public function vendors(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $vendors = $this->scoped(EconomyVendor::query(), $team)->where('status', 'active')->with('offers')->latest()->get();

        return response()->json(['data' => $vendors->map(fn (EconomyVendor $vendor): array => $this->vendorResource($vendor))->values()]);
    }

    public function createVendor(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'data' => ['array']]);
        $team = $request->user()?->currentTeam;
        $vendor = app(EconomyManager::class)->createVendor($data['name'], $data['data'] ?? [], $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->vendorResource($vendor)], 201);
    }

    public function addOffer(Request $request, EconomyVendor $vendor): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($this->inScope($vendor, $team), 404);
        $data = $request->validate(['item_key' => ['required', 'string', 'max:120'], 'currency_code' => ['required', 'string', 'max:30'], 'unit_price' => ['required', 'integer', 'min:1'], 'stock' => ['nullable', 'integer', 'min:0'], 'max_per_actor' => ['nullable', 'integer', 'min:1']]);
        $offer = app(EconomyManager::class)->addOffer($vendor, $data['item_key'], $data['currency_code'], (int) $data['unit_price'], $data['stock'] ?? null, $data['max_per_actor'] ?? null, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->offerResource($offer)], 201);
    }

    public function purchaseOffer(Request $request, EconomyVendorOffer $offer): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($this->inScope($offer->vendor()->firstOrFail(), $team), 404);
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $updated = app(EconomyManager::class)->purchaseOffer((string) $request->user()->getAuthIdentifier(), $offer, (int) ($data['quantity'] ?? 1), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->offerResource($updated)]);
    }

    public function listings(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $listings = $this->scoped(EconomyListing::query(), $team)->where('status', 'active')->latest()->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

        return response()->json(['data' => $listings->through(fn (EconomyListing $listing): array => $this->listingResource($listing))]);
    }

    public function createListing(Request $request): JsonResponse
    {
        $data = $request->validate(['item_key' => ['required', 'string', 'max:120'], 'currency_code' => ['required', 'string', 'max:30'], 'quantity' => ['required', 'integer', 'min:1', 'max:100000'], 'unit_price' => ['required', 'integer', 'min:1'], 'asset_reference' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:120']]);
        $team = $request->user()?->currentTeam;
        $listing = app(EconomyManager::class)->createListing((string) $request->user()->getAuthIdentifier(), $data['item_key'], $data['currency_code'], (int) $data['quantity'], (int) $data['unit_price'], $data['asset_reference'] ?? [], $data['idempotency_key'] ?? null, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->listingResource($listing)], 201);
    }

    public function purchaseListing(Request $request, EconomyListing $listing): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($this->inScope($listing, $team), 404);
        $updated = app(EconomyManager::class)->purchaseListing((string) $request->user()->getAuthIdentifier(), $listing, $request->input('idempotency_key'), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->listingResource($updated)]);
    }

    public function cancelListing(Request $request, EconomyListing $listing): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($this->inScope($listing, $team), 404);
        $updated = app(EconomyManager::class)->cancelListing((string) $request->user()->getAuthIdentifier(), $listing, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->listingResource($updated)]);
    }

    private function resource(EconomyRecord $economy): array
    {
        return ['id' => (string) $economy->getKey(), 'type' => 'browser-game-economy-currency', 'attributes' => ['name' => $economy->name, 'code' => $economy->code, 'precision' => $economy->precision, 'fee_basis_points' => $economy->fee_basis_points, 'status' => $economy->status, 'data' => $economy->data]];
    }

    private function listingResource(EconomyListing $listing): array
    {
        return ['id' => (string) $listing->getKey(), 'type' => 'browser-game-economy-listing', 'attributes' => $listing->only(['seller_id', 'buyer_id', 'item_key', 'currency_code', 'quantity', 'unit_price', 'fee', 'status', 'sold_at', 'asset_reference'])];
    }

    private function walletResource(EconomyWallet $wallet): array
    {
        return ['id' => (string) $wallet->getKey(), 'type' => 'browser-game-economy-wallet', 'attributes' => ['actor_id' => $wallet->actor_id, 'currency_code' => $wallet->currency_code, 'balance' => $wallet->balance, 'created_at' => $wallet->created_at?->toISOString(), 'updated_at' => $wallet->updated_at?->toISOString()]];
    }

    private function ledgerResource(object $entry): array
    {
        return ['id' => (string) $entry->getKey(), 'type' => 'browser-game-economy-ledger-entry', 'attributes' => $entry->only(['actor_id', 'currency_code', 'amount', 'balance_after', 'entry_type', 'source', 'metadata'])];
    }

    private function vendorResource(EconomyVendor $vendor): array
    {
        return ['id' => (string) $vendor->getKey(), 'type' => 'browser-game-economy-vendor', 'attributes' => $vendor->only(['name', 'status', 'data']), 'relationships' => ['offers' => ['data' => $vendor->offers->map(fn (EconomyVendorOffer $offer): array => ['id' => (string) $offer->getKey(), 'type' => 'browser-game-economy-offer'])->values()]]];
    }

    private function offerResource(EconomyVendorOffer $offer): array
    {
        return ['id' => (string) $offer->getKey(), 'type' => 'browser-game-economy-offer', 'attributes' => $offer->only(['vendor_id', 'item_key', 'currency_code', 'unit_price', 'stock', 'max_per_actor', 'data'])];
    }

    private function scoped($query, $team)
    {
        return $query
            ->where(fn ($scope) => $scope->whereNull('tenant_id')->orWhere('tenant_id', $team?->getAttribute('tenant_id')))
            ->where(fn ($scope) => $scope->whereNull('team_id')->orWhere('team_id', $team?->getKey()));
    }

    private function inScope(object $record, $team): bool
    {
        if ($team === null) {
            return $record->tenant_id === null && $record->team_id === null;
        }

        return ($record->tenant_id === null || (string) $record->tenant_id === (string) $team->getAttribute('tenant_id'))
            && ($record->team_id === null || (string) $record->team_id === (string) $team->getKey());
    }
}
