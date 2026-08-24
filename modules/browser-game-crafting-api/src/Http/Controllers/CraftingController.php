<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Crafting\Models\CraftingRecord;
use Liberu\BrowserGame\Crafting\Queries\CraftingQuery;

final class CraftingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(CraftingQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(CraftingRecord $crafting): JsonResponse
    {
        return response()->json(['data' => $this->resource($crafting)]);
    }

    private function resource(Model $crafting): array
    {
        return ['id' => (string) $crafting->getKey(), 'type' => 'browser-game-crafting', 'attributes' => ['name' => $crafting->getAttribute('name'), 'status' => $crafting->getAttribute('status'), 'data' => $crafting->getAttribute('data'), 'tenant_id' => $crafting->getAttribute('tenant_id'), 'team_id' => $crafting->getAttribute('team_id')]];
    }
}
