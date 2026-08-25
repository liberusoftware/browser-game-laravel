<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Quests\Models\Quest;
use Liberu\BrowserGame\Quests\Queries\QuestQuery;
use Liberu\BrowserGame\Quests\Support\QuestsManager;

final class QuestsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $items = app(QuestQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->latest()->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

        return response()->json(['data' => $items->through(fn (Quest $item): array => $this->resource($item))]);
    }

    public function show(Request $request, Quest $quest): JsonResponse
    {
        $quest = $this->authorizedQuest($request, $quest);

        return response()->json(['data' => $this->resource($quest)]);
    }

    public function progress(Request $request, Quest $quest): JsonResponse
    {
        $quest = $this->authorizedQuest($request, $quest);
        $validated = $request->validate(['progress' => ['required', 'array'], 'status' => ['nullable', 'in:in_progress,completed'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);
        $result = app(QuestsManager::class)->progress($quest, (string) $request->user()->getKey(), $validated['progress'], $validated['status'] ?? 'in_progress', $validated['idempotency_key'] ?? $request->header('Idempotency-Key'));

        return response()->json(['data' => $this->progressResource($result)]);
    }

    public function accept(Request $request, Quest $quest): JsonResponse
    {
        $quest = $this->authorizedQuest($request, $quest);
        $data = $request->validate(['completed_quests' => ['array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->progressResource(app(QuestsManager::class)->accept($quest, (string) $request->user()->getKey(), $data, $data['idempotency_key'] ?? $request->header('Idempotency-Key')))], 201);
    }

    public function complete(Request $request, Quest $quest): JsonResponse
    {
        $quest = $this->authorizedQuest($request, $quest);
        $data = $request->validate(['progress' => ['nullable', 'array'], 'idempotency_key' => ['nullable', 'string', 'max:128']]);

        return response()->json(['data' => $this->progressResource(app(QuestsManager::class)->complete($quest, (string) $request->user()->getKey(), $data['idempotency_key'] ?? $request->header('Idempotency-Key'), $data['progress'] ?? null))]);
    }

    public function abandon(Request $request, Quest $quest): JsonResponse
    {
        $quest = $this->authorizedQuest($request, $quest);

        return response()->json(['data' => $this->progressResource(app(QuestsManager::class)->abandon($quest, (string) $request->user()->getKey()))]);
    }

    private function authorizedQuest(Request $request, Quest $quest): Quest
    {
        $team = $request->user()?->currentTeam;

        return app(QuestQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($quest->getKey())->firstOrFail();
    }

    private function resource(Model $quest): array
    {
        return ['id' => (string) $quest->getKey(), 'type' => 'browser-game-quest', 'attributes' => ['slug' => $quest->getAttribute('slug'), 'name' => $quest->getAttribute('name'), 'storyline' => $quest->getAttribute('storyline'), 'status' => $quest->getAttribute('status'), 'objectives' => $quest->getAttribute('objectives'), 'prerequisites' => $quest->getAttribute('prerequisites'), 'branches' => $quest->getAttribute('branches'), 'dialogue' => $quest->getAttribute('dialogue'), 'rewards' => $quest->getAttribute('rewards'), 'repeatable' => $quest->getAttribute('repeatable')]];
    }

    private function progressResource(Model $progress): array
    {
        return ['id' => (string) $progress->getKey(), 'type' => 'browser-game-quest-progress', 'attributes' => $progress->only(['quest_id', 'actor_id', 'status', 'progress', 'accepted_at', 'completed_at', 'reward_claimed_at', 'completion_count'])];
    }
}
