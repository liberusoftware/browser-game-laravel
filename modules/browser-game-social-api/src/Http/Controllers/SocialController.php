<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Social\Models\SocialRecord;
use Liberu\BrowserGame\Social\Queries\SocialQuery;
use Liberu\BrowserGame\Social\Support\SocialManager;

final class SocialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $items = app(SocialQuery::class)->visible(null, $teamId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (SocialRecord $item): array => $this->resource($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'kind' => ['required', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->create($v['name'], $v['kind'], (string) $request->user()->getAuthIdentifier(), $v['data'] ?? [], null, $request->user()?->currentTeam?->getKey(), $v['idempotency_key'] ?? null))], 201);
    }

    public function member(Request $request, SocialRecord $social): JsonResponse
    {
        $v = $request->validate(['actor_id' => ['required', 'string'], 'role' => ['nullable', 'string']]);

        return response()->json(['data' => app(SocialManager::class)->addMember($social, $v['actor_id'], $v['role'] ?? 'member')], 201);
    }

    public function message(Request $request, SocialRecord $social): JsonResponse
    {
        $v = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->send((string) $request->user()->getAuthIdentifier(), $social, $v['body']))], 201);
    }

    public function report(Request $request): JsonResponse
    {
        $v = $request->validate(['target_id' => ['required', 'string'], 'reason' => ['required', 'string', 'max:1000'], 'data' => ['array']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->report((string) $request->user()->getAuthIdentifier(), $v['target_id'], $v['reason'], $v['data'] ?? []))], 201);
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
