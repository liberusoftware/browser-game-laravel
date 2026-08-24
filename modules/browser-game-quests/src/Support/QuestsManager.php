<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Quests\Events\QuestProgressed;
use Liberu\BrowserGame\Quests\Models\Quest;
use Liberu\BrowserGame\Quests\Models\QuestProgress;

final class QuestsManager
{
    public function define(string $name, string $slug, array $objectives = [], array $rewards = [], bool $repeatable = false, ?string $tenantId = null, ?string $teamId = null): Quest
    {
        if (trim($name) === '' || trim($slug) === '' || $objectives === []) {
            throw ValidationException::withMessages(['quest' => 'Name, slug, and objectives are required.']);
        }

return Quest::query()->create(['id' => (string) Str::uuid(), 'name' => $name, 'slug' => $slug, 'objectives' => $objectives, 'rewards' => $rewards, 'repeatable' => $repeatable, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'status' => 'active']);
    }

    public function progress(Quest $quest, string $actorId, array $progress, string $status = 'in_progress'): QuestProgress
    {
        if (trim($actorId) === '' || ! in_array($status, ['in_progress', 'completed', 'abandoned'], true)) {
            throw ValidationException::withMessages(['progress' => 'Valid actor and status are required.']);
        } $result = DB::transaction(fn (): QuestProgress => QuestProgress::query()->updateOrCreate(['quest_id' => $quest->getKey(), 'actor_id' => $actorId], ['id' => (string) Str::uuid(), 'progress' => $progress, 'status' => $status]));
        QuestProgressed::dispatch((string) $quest->getKey(), $actorId, $status);

        return $result;
    }
}
