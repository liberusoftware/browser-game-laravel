<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Commerce\Models\CommerceRecord;
use Liberu\BrowserGame\Commerce\Queries\CommerceQuery;

final class CommerceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(CommerceQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(Request $request, CommerceRecord $commerce): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $commerce = app(CommerceQuery::class)->visible(null, (string) $teamId)
            ->whereKey($commerce->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($commerce)]);
    }

    private function resource(Model $commerce): array
    {
        return ['id' => (string) $commerce->getKey(), 'type' => 'browser-game-commerce', 'attributes' => ['name' => $commerce->getAttribute('name'), 'status' => $commerce->getAttribute('status'), 'data' => $commerce->getAttribute('data'), 'tenant_id' => $commerce->getAttribute('tenant_id'), 'team_id' => $commerce->getAttribute('team_id')]];
    }
}
