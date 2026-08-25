<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Social\Models\SocialActivity;
use Liberu\BrowserGame\Social\Models\SocialRecord;
use Liberu\BrowserGame\Social\Queries\SocialQuery;
use Liberu\BrowserGame\Social\Support\SocialManager;

final class SocialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $items = app(SocialQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate($pageSize);

        return response()->json(['data' => $items->through(fn (SocialRecord $item): array => $this->resource($item))]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'kind' => ['required', 'string'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        $team = $request->user()?->currentTeam;

        return response()->json(['data' => $this->resource(app(SocialManager::class)->create($v['name'], $v['kind'], (string) $request->user()->getAuthIdentifier(), $v['data'] ?? [], $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), $v['idempotency_key'] ?? null))], 201);
    }

    public function friendRequest(Request $request): JsonResponse
    {
        $v = $request->validate(['target_id' => ['required', 'string', 'different:'.(string) $request->user()->getAuthIdentifier()], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->createFriendRequest((string) $request->user()->getAuthIdentifier(), $v['target_id'], $this->tenantId($request), $this->teamId($request), $v['idempotency_key'] ?? null))], 201);
    }

    public function respondToFriendRequest(Request $request, SocialRecord $social): JsonResponse
    {
        $social = $this->authorizedSocial($request, $social);
        $v = $request->validate(['status' => ['required', 'in:accepted,declined']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->respondToFriendRequest($social, (string) $request->user()->getAuthIdentifier(), $v['status']))]);
    }

    public function createGroup(Request $request, string $kind): JsonResponse
    {
        abort_unless(in_array($kind, ['party', 'chat', 'guild', 'alliance'], true), 404);
        $v = $request->validate(['name' => ['required', 'string', 'max:255'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $manager = app(SocialManager::class);
        $actorId = (string) $request->user()->getAuthIdentifier();
        $record = match ($kind) {
            'party' => $manager->createParty($actorId, $v['name'], $v['data'] ?? [], $this->tenantId($request), $this->teamId($request), $v['idempotency_key'] ?? null),
            'chat' => $manager->createChat($actorId, $v['name'], $v['data'] ?? [], $this->tenantId($request), $this->teamId($request), $v['idempotency_key'] ?? null),
            'guild' => $manager->createGuild($actorId, $v['name'], $v['data'] ?? [], $this->tenantId($request), $this->teamId($request), $v['idempotency_key'] ?? null),
            default => $manager->createAlliance($actorId, $v['name'], $v['data'] ?? [], $this->tenantId($request), $this->teamId($request), $v['idempotency_key'] ?? null),
        };

        return response()->json(['data' => $this->resource($record)], 201);
    }

    public function mail(Request $request): JsonResponse
    {
        $v = $request->validate(['recipient_id' => ['required', 'string'], 'body' => ['required', 'string', 'max:10000'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->createMail((string) $request->user()->getAuthIdentifier(), $v['recipient_id'], $v['body'], $this->tenantId($request), $this->teamId($request), $v['idempotency_key'] ?? null))], 201);
    }

    public function member(Request $request, SocialRecord $social): JsonResponse
    {
        $social = $this->authorizedSocial($request, $social);
        $v = $request->validate(['actor_id' => ['required', 'string'], 'role' => ['nullable', 'string']]);

        return response()->json(['data' => app(SocialManager::class)->addMember($social, $v['actor_id'], $v['role'] ?? 'member', [], (string) $request->user()->getAuthIdentifier())], 201);
    }

    public function message(Request $request, SocialRecord $social): JsonResponse
    {
        $social = $this->authorizedSocial($request, $social);
        $v = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->send((string) $request->user()->getAuthIdentifier(), $social, $v['body']))], 201);
    }

    public function report(Request $request): JsonResponse
    {
        $v = $request->validate(['target_id' => ['required', 'string'], 'reason' => ['required', 'string', 'max:1000'], 'data' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->resource(app(SocialManager::class)->report((string) $request->user()->getAuthIdentifier(), $v['target_id'], $v['reason'], $v['data'] ?? [], $this->tenantId($request), $this->teamId($request), $v['idempotency_key'] ?? null))], 201);
    }

    public function permissions(Request $request, SocialRecord $social): JsonResponse
    {
        $social = $this->authorizedSocial($request, $social);
        $v = $request->validate(['member_id' => ['required', 'string'], 'permissions' => ['required', 'array']]);

        return response()->json(['data' => app(SocialManager::class)->updatePermissions($social, (string) $request->user()->getAuthIdentifier(), $v['member_id'], $v['permissions'])]);
    }

    public function activity(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($teamId !== null, 404);
        $pageSize = min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100);
        $items = SocialActivity::query()->whereIn('target_id', SocialRecord::query()->where('team_id', $teamId)->select('id'))->latest()->paginate($pageSize);

        return response()->json([
            'data' => $items->through(fn (SocialActivity $item): array => [
                'id' => (string) $item->getKey(),
                'type' => 'browser-game-social-activity',
                'attributes' => [
                    'actor_id' => $item->actor_id,
                    'kind' => $item->kind,
                    'target_id' => $item->target_id,
                    'data' => $item->data,
                    'created_at' => $item->created_at?->toISOString(),
                ],
            ]),
        ]);
    }

    public function show(Request $request, SocialRecord $social): JsonResponse
    {
        $social = $this->authorizedSocial($request, $social);

        return response()->json(['data' => $this->resource($social)]);
    }

    private function resource(SocialRecord $social): array
    {
        return ['id' => (string) $social->getKey(), 'type' => 'browser-game-social', 'attributes' => ['name' => $social->name, 'kind' => $social->kind, 'status' => $social->status, 'owner_id' => $social->owner_id, 'target_id' => $social->target_id, 'body' => $social->body, 'data' => $social->data, 'tenant_id' => $social->tenant_id, 'team_id' => $social->team_id, 'created_at' => $social->created_at?->toISOString(), 'updated_at' => $social->updated_at?->toISOString()]];
    }

    private function authorizedSocial(Request $request, SocialRecord $social): SocialRecord
    {
        $teamId = $this->teamId($request);
        abort_unless($teamId !== null, 404);

        return app(SocialQuery::class)->visible($this->tenantId($request), (string) $teamId)->whereKey($social->getKey())->firstOrFail();
    }

    private function teamId(Request $request): ?string
    {
        $teamId = $request->user()?->currentTeam?->getKey();

        return $teamId === null ? null : (string) $teamId;
    }

    private function tenantId(Request $request): ?string
    {
        $tenantId = $request->user()?->currentTeam?->getAttribute('tenant_id');

        return $tenantId === null ? null : (string) $tenantId;
    }
}
