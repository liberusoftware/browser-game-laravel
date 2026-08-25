<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Quests\Events\QuestCompleted;
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
        if (Quest::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['slug' => 'The quest slug is already in use.']);
        }

        return Quest::query()->create(['id' => (string) Str::uuid(), 'name' => $name, 'slug' => $slug, 'objectives' => $objectives, 'rewards' => $rewards, 'repeatable' => $repeatable, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'status' => 'active']);
    }

    /** @param array<string, mixed> $context */
    public function accept(Quest $quest, string $actorId, array $context = [], ?string $idempotencyKey = null): QuestProgress
    {
        $quest = $quest->fresh() ?? $quest;
        $this->assertActor($actorId);
        if ($quest->status !== 'active') {
            throw ValidationException::withMessages(['quest' => 'The quest is not available.']);
        }
        $completed = array_map('strval', (array) ($context['completed_quests'] ?? []));
        $existingProgress = QuestProgress::query()->where(['quest_id' => $quest->getKey(), 'actor_id' => $actorId])->first();
        foreach ((array) ($quest->prerequisites ?? []) as $prerequisite) {
            if (! in_array((string) (is_array($prerequisite) ? ($prerequisite['quest'] ?? '') : $prerequisite), $completed, true) && ! ($quest->repeatable && (int) ($existingProgress?->completion_count ?? 0) > 0)) {
                throw ValidationException::withMessages(['prerequisites' => 'The quest prerequisites are not complete.']);
            }
        }

        return DB::transaction(function () use ($quest, $actorId, $idempotencyKey): QuestProgress {
            $progress = QuestProgress::query()->where(['quest_id' => $quest->getKey(), 'actor_id' => $actorId])->lockForUpdate()->first();
            if ($progress !== null && $idempotencyKey !== null && $progress->last_operation_key === $idempotencyKey) {
                return $progress;
            }
            if ($progress !== null && $progress->status === 'in_progress') {
                return $progress;
            }
            $progress ??= new QuestProgress(['id' => (string) Str::uuid(), 'quest_id' => $quest->getKey(), 'actor_id' => $actorId]);
            $progress->fill(['status' => 'in_progress', 'progress' => (array) ($progress->progress ?? []), 'accepted_at' => now(), 'completed_at' => null, 'reward_claimed_at' => null, 'last_operation_key' => $idempotencyKey]);
            $progress->save();

            return $progress->fresh();
        });
    }

    public function progress(Quest $quest, string $actorId, array $progress, string $status = 'in_progress', ?string $idempotencyKey = null): QuestProgress
    {
        $this->assertActor($actorId);
        if (! in_array($status, ['in_progress', 'completed'], true)) {
            throw ValidationException::withMessages(['progress' => 'Progress updates may only be active or completed.']);
        }
        $current = QuestProgress::query()->where(['quest_id' => $quest->getKey(), 'actor_id' => $actorId])->first();
        if ($current === null || $current->status !== 'in_progress') {
            $current = $this->accept($quest, $actorId);
        }
        $merged = array_replace((array) $current->progress, $progress);
        $result = DB::transaction(function () use ($quest, $actorId, $merged, $idempotencyKey): QuestProgress {
            $record = QuestProgress::query()->where(['quest_id' => $quest->getKey(), 'actor_id' => $actorId])->lockForUpdate()->firstOrFail();
            if ($idempotencyKey !== null && $record->last_operation_key === $idempotencyKey) {
                return $record;
            }
            $record->fill(['progress' => $merged, 'last_operation_key' => $idempotencyKey]);
            $record->save();

            return $record->fresh();
        });
        if ($status === 'completed') {
            return $this->complete($quest, $actorId, $idempotencyKey, $result->progress);
        }
        QuestProgressed::dispatch((string) $quest->getKey(), $actorId, 'in_progress');

        return $result;
    }

    public function complete(Quest $quest, string $actorId, ?string $idempotencyKey = null, ?array $progress = null): QuestProgress
    {
        $this->assertActor($actorId);
        $rewards = [];
        $completionCount = 0;
        $result = DB::transaction(function () use ($quest, $actorId, $idempotencyKey, $progress, &$rewards, &$completionCount): QuestProgress {
            $record = QuestProgress::query()->where(['quest_id' => $quest->getKey(), 'actor_id' => $actorId])->lockForUpdate()->firstOrFail();
            if ($record->status === 'completed' && ! $quest->repeatable) {
                return $record;
            }
            if ($progress !== null) {
                $record->progress = $progress;
            }
            if (! $this->objectivesComplete($quest, (array) $record->progress)) {
                throw ValidationException::withMessages(['progress' => 'All quest objectives must be complete.']);
            }
            $record->fill(['status' => 'completed', 'completed_at' => now(), 'reward_claimed_at' => now(), 'completion_count' => (int) $record->completion_count + 1, 'last_operation_key' => $idempotencyKey]);
            $record->save();
            $rewards = (array) $quest->rewards;
            $completionCount = (int) $record->completion_count;

            return $record->fresh();
        });
        QuestProgressed::dispatch((string) $quest->getKey(), $actorId, 'completed');
        if ($rewards !== []) {
            QuestCompleted::dispatch((string) $quest->getKey(), $actorId, $rewards, $completionCount);
        }

        return $result;
    }

    public function abandon(Quest $quest, string $actorId): QuestProgress
    {
        $this->assertActor($actorId);
        $result = QuestProgress::query()->where(['quest_id' => $quest->getKey(), 'actor_id' => $actorId])->firstOrFail();
        if ($result->status !== 'in_progress') {
            throw ValidationException::withMessages(['quest' => 'Only active quests can be abandoned.']);
        }
        $result->update(['status' => 'abandoned']);
        QuestProgressed::dispatch((string) $quest->getKey(), $actorId, 'abandoned');

        return $result->refresh();
    }

    private function objectivesComplete(Quest $quest, array $progress): bool
    {
        foreach ((array) $quest->objectives as $key => $objective) {
            $objectiveKey = is_array($objective) ? (string) ($objective['key'] ?? $key) : (string) $key;
            $required = is_array($objective) ? (int) ($objective['quantity'] ?? $objective['required'] ?? 1) : (int) $objective;
            $actualValue = $progress[$objectiveKey] ?? 0;
            $actual = is_array($actualValue) ? (int) ($actualValue['quantity'] ?? 0) : (int) $actualValue;
            if ($actual < $required) {
                return false;
            }
        }

        return true;
    }

    private function assertActor(string $actorId): void
    {
        if (trim($actorId) === '') {
            throw ValidationException::withMessages(['actor_id' => 'An actor is required.']);
        }
    }
}
