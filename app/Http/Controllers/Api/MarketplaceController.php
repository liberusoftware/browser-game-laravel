<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\MarketplaceListing;
use App\Models\Player;
use App\Services\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    protected MarketplaceService $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        $this->marketplaceService = $marketplaceService;
    }

    /**
     * Get active marketplace listings.
     */
    public function index(Request $request): JsonResponse
    {
        $listings = MarketplaceListing::active()
            ->with(['seller', 'item'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Create a new marketplace listing.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|integer|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'price_per_unit' => 'required|integer|min:1',
        ]);

        $seller = $this->player($request);
        $item = Item::findOrFail($request->input('item_id'));
        $listing = $this->marketplaceService->createListing(
            $seller,
            $item,
            (int) $request->input('quantity'),
            (int) $request->input('price_per_unit')
        );

        if (! $listing) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have enough of this item to list.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Listing created successfully',
            'data' => $listing->load(['seller', 'item']),
        ], 201);
    }

    /**
     * Purchase a marketplace listing.
     */
    public function purchase(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $result = $this->marketplaceService->purchaseListing($this->player($request), $listing);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Cancel a marketplace listing.
     */
    public function cancel(Request $request, MarketplaceListing $listing): JsonResponse
    {
        $seller = $this->player($request);
        $cancelled = $this->marketplaceService->cancelListing($seller, $listing);

        if ($cancelled) {
            return response()->json([
                'success' => true,
                'message' => 'Listing cancelled successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to cancel listing.',
        ], 422);
    }

    private function player(Request $request): Player
    {
        $user = $request->user();
        abort_unless($user !== null, 403, 'Player authentication required.');

        $player = Player::query()->where('email', $user->email)->first();
        abort_unless($player !== null, 403, 'Player authentication required.');

        return $player;
    }
}
