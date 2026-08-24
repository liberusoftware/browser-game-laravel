<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Social\Models\SocialRecord;
use Liberu\BrowserGame\Social\Queries\SocialQuery;

final class SocialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(SocialQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(Request $request, SocialRecord $social): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $social = app(SocialQuery::class)->visible(null, (string) $teamId)
            ->whereKey($social->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($social)]);
    }

    private function resource(Model $social): array
    {
        return ['id' => (string) $social->getKey(), 'type' => 'browser-game-social', 'attributes' => ['name' => $social->getAttribute('name'), 'status' => $social->getAttribute('status'), 'data' => $social->getAttribute('data'), 'tenant_id' => $social->getAttribute('tenant_id'), 'team_id' => $social->getAttribute('team_id')]];
    }
}
