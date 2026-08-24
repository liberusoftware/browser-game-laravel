<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Economy\Models\EconomyRecord;
use Liberu\BrowserGame\Economy\Queries\EconomyQuery;

final class EconomyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(EconomyQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(EconomyRecord $economy): JsonResponse
    {
        return response()->json(['data' => $this->resource($economy)]);
    }

    private function resource(Model $economy): array
    {
        return ['id' => (string) $economy->getKey(), 'type' => 'browser-game-economy', 'attributes' => ['name' => $economy->getAttribute('name'), 'status' => $economy->getAttribute('status'), 'data' => $economy->getAttribute('data'), 'tenant_id' => $economy->getAttribute('tenant_id'), 'team_id' => $economy->getAttribute('team_id')]];
    }
}
