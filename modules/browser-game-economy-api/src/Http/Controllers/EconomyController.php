<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Economy\Models\EconomyListing;
use Liberu\BrowserGame\Economy\Models\EconomyRecord;
use Liberu\BrowserGame\Economy\Models\EconomyWallet;
use Liberu\BrowserGame\Economy\Queries\EconomyQuery;
use Liberu\BrowserGame\Economy\Support\EconomyManager;

final class EconomyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(EconomyQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (EconomyRecord $item): array => $this->resource($item))]);
    }

    public function show(Request $request, EconomyRecord $economy): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $economy = app(EconomyQuery::class)->visible(null, (string) $teamId)
            ->whereKey($economy->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($economy)]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $wallets = EconomyWallet::query()->where('actor_id', (string) $request->user()->getAuthIdentifier())->get();

        return response()->json(['data' => $wallets->map(fn (EconomyWallet $wallet): array => ['id' => (string) $wallet->getKey(), 'type' => 'browser-game-economy-wallet', 'attributes' => $wallet->only(['currency_code', 'balance'])])->values()]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate(['recipient_id' => ['required', 'string', 'max:120'], 'currency_code' => ['required', 'string', 'max:30'], 'amount' => ['required', 'integer', 'min:1'], 'idempotency_key' => ['nullable', 'string', 'max:120']]);
        $result = app(EconomyManager::class)->transfer((string) $request->user()->getAuthIdentifier(), $data['recipient_id'], $data['currency_code'], (int) $data['amount'], $data['idempotency_key'] ?? null);

        return response()->json(['data' => ['debit' => $result['debit']->toArray(), 'credit' => $result['credit']->toArray()]]);
    }

    public function listings(Request $request): JsonResponse
    {
        $listings = EconomyListing::query()->where('status', 'active')->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $listings->through(fn (EconomyListing $listing): array => $this->listingResource($listing))]);
    }

    public function createListing(Request $request): JsonResponse
    {
        $data = $request->validate(['item_key' => ['required', 'string', 'max:120'], 'currency_code' => ['required', 'string', 'max:30'], 'quantity' => ['required', 'integer', 'min:1', 'max:100000'], 'unit_price' => ['required', 'integer', 'min:1'], 'asset_reference' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:120']]);
        $listing = app(EconomyManager::class)->createListing((string) $request->user()->getAuthIdentifier(), $data['item_key'], $data['currency_code'], (int) $data['quantity'], (int) $data['unit_price'], $data['asset_reference'] ?? [], $data['idempotency_key'] ?? null);

        return response()->json(['data' => $this->listingResource($listing)], 201);
    }

    public function purchaseListing(Request $request, EconomyListing $listing): JsonResponse
    {
        $updated = app(EconomyManager::class)->purchaseListing((string) $request->user()->getAuthIdentifier(), $listing, $request->input('idempotency_key'));

        return response()->json(['data' => $this->listingResource($updated)]);
    }

    public function cancelListing(Request $request, EconomyListing $listing): JsonResponse
    {
        $updated = app(EconomyManager::class)->cancelListing((string) $request->user()->getAuthIdentifier(), $listing);

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
}
