<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsApi\Http\Controllers;

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
        $items = app(QuestQuery::class)->visible(null, null)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $items->through(fn (Quest $item): array => $this->resource($item))]);
    }

    public function show(Quest $quest): JsonResponse
    {
        return response()->json(['data' => $this->resource($quest)]);
    }

    public function progress(Request $request, Quest $quest): JsonResponse
    {
        $result = app(QuestsManager::class)->progress($quest, (string) $request->user()->getKey(), (array) $request->input('progress', []), (string) $request->input('status', 'in_progress'));

        return response()->json(['data' => ['id' => (string) $result->getKey(), 'type' => 'browser-game-quest-progress', 'attributes' => ['status' => $result->getAttribute('status'), 'progress' => $result->getAttribute('progress')]]]);
    }

    private function resource(Model $quest): array
    {
        return ['id' => (string) $quest->getKey(), 'type' => 'browser-game-quest', 'attributes' => ['slug' => $quest->getAttribute('slug'), 'name' => $quest->getAttribute('name'), 'storyline' => $quest->getAttribute('storyline'), 'status' => $quest->getAttribute('status'), 'objectives' => $quest->getAttribute('objectives'), 'prerequisites' => $quest->getAttribute('prerequisites'), 'branches' => $quest->getAttribute('branches'), 'dialogue' => $quest->getAttribute('dialogue'), 'rewards' => $quest->getAttribute('rewards'), 'repeatable' => $quest->getAttribute('repeatable')]];
    }
}
