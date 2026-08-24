<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;
use Liberu\BrowserGame\Competition\Queries\CompetitionQuery;

final class CompetitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(CompetitionQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Model $item): array => $this->resource($item))]);
    }

    public function show(Request $request, CompetitionRecord $competition): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        $competition = app(CompetitionQuery::class)->visible(null, (string) $teamId)
            ->whereKey($competition->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($competition)]);
    }

    private function resource(Model $competition): array
    {
        return ['id' => (string) $competition->getKey(), 'type' => 'browser-game-competition', 'attributes' => ['name' => $competition->getAttribute('name'), 'status' => $competition->getAttribute('status'), 'data' => $competition->getAttribute('data'), 'tenant_id' => $competition->getAttribute('tenant_id'), 'team_id' => $competition->getAttribute('team_id')]];
    }
}
