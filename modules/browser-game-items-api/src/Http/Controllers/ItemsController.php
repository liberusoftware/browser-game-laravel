<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Items\Models\ItemsRecord;
use Liberu\BrowserGame\Items\Queries\ItemsQuery;

final class ItemsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(ItemsQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(ItemsRecord $items): JsonResponse
    {
        return response()->json(['data' => $this->resource($items)]);
    }

    private function resource(Model $items): array
    {
        return ['id' => (string) $items->getKey(), 'type' => 'browser-game-items', 'attributes' => ['name' => $items->getAttribute('name'), 'status' => $items->getAttribute('status'), 'data' => $items->getAttribute('data'), 'tenant_id' => $items->getAttribute('tenant_id'), 'team_id' => $items->getAttribute('team_id')]];
    }
}
