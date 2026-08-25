<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Collections\Events\CollectionProgressRecorded;
use Liberu\BrowserGame\Collections\Events\CollectionRewardGranted;
use Liberu\BrowserGame\Collections\Events\CollectionsDefined;
use Liberu\BrowserGame\Collections\Models\CollectionProgress;
use Liberu\BrowserGame\Collections\Models\CollectionsRecord;

final class CollectionsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): CollectionsRecord => CollectionsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        CollectionsDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function addEntry(CollectionsRecord $collection, string $entryKey, string $name, int $requiredQuantity = 1, array $reward = [], array $data = []): mixed
    {
        if (trim($entryKey) === '' || trim($name) === '' || $requiredQuantity < 1) {
            throw ValidationException::withMessages(['entry' => 'A valid collection entry is required.']);
        }

        return $collection->entries()->create(['entry_key' => $entryKey, 'name' => $name, 'required_quantity' => $requiredQuantity, 'reward' => $reward, 'data' => $data]);
    }

    public function defineCollection(string $name, string $kind = 'achievement', array $data = [], bool $repeatable = false, ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        if (! in_array($kind, ['achievement', 'title', 'reputation', 'pet', 'mount', 'housing', 'cosmetic'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported collection kind.']);
        }
        $collection = $this->define($name, $data, $tenantId, $teamId);
        $collection->update(['kind' => $kind, 'repeatable' => $repeatable]);

        return $collection->fresh();
    }

    public function defineAchievement(string $name, array $data = [], bool $repeatable = false, ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        return $this->defineCollection($name, 'achievement', $data, $repeatable, $tenantId, $teamId);
    }

    public function defineTitle(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        return $this->defineCollection($name, 'title', $data, false, $tenantId, $teamId);
    }

    public function defineReputation(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        return $this->defineCollection($name, 'reputation', $data, true, $tenantId, $teamId);
    }

    public function definePet(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        return $this->defineCollection($name, 'pet', $data, false, $tenantId, $teamId);
    }

    public function defineMount(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        return $this->defineCollection($name, 'mount', $data, false, $tenantId, $teamId);
    }

    public function defineHousing(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        return $this->defineCollection($name, 'housing', $data, false, $tenantId, $teamId);
    }

    public function defineCosmetic(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        return $this->defineCollection($name, 'cosmetic', $data, false, $tenantId, $teamId);
    }

    public function record(string $actorId, CollectionsRecord $collection, string $entryKey, int $quantity = 1, ?string $idempotencyKey = null): CollectionProgress
    {
        if (trim($actorId) === '' || trim($entryKey) === '' || $quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be positive.']);
        }
        if ($collection->status !== 'active') {
            throw ValidationException::withMessages(['collection' => 'The collection is not active.']);
        }

        $completed = false;
        $reward = [];
        $completionCount = 0;
        $progress = DB::transaction(function () use ($actorId, $collection, $entryKey, $quantity, $idempotencyKey, &$completed, &$reward, &$completionCount): CollectionProgress {
            $entry = $collection->entries()->lockForUpdate()->where('entry_key', $entryKey)->firstOrFail();
            $progress = CollectionProgress::query()->where([
                'actor_id' => $actorId,
                'collection_id' => $collection->getKey(),
                'entry_key' => $entryKey,
            ])->lockForUpdate()->first();
            if ($progress === null) {
                $progress = CollectionProgress::query()->create([
                    'actor_id' => $actorId,
                    'collection_id' => $collection->getKey(),
                    'entry_key' => $entryKey,
                    'quantity' => 0,
                    'completion_count' => 0,
                ]);
            }
            if ($idempotencyKey !== null && $progress->last_operation_key === $idempotencyKey) {
                $completed = $progress->completed_at !== null;
                $completionCount = (int) $progress->completion_count;

                return $progress->fresh();
            }
            if ($progress->completed_at !== null && ! $collection->repeatable) {
                $progress->last_operation_key = $idempotencyKey;
                $progress->save();

                return $progress->fresh();
            }
            if ($progress->completed_at !== null && $collection->repeatable) {
                $progress->quantity = 0;
                $progress->completed_at = null;
                $progress->reward_claimed_at = null;
            }
            $progress->quantity = min((int) $entry->required_quantity, (int) $progress->quantity + $quantity);
            $progress->last_operation_key = $idempotencyKey;
            if ($progress->quantity >= (int) $entry->required_quantity) {
                $progress->completed_at = now();
                $progress->reward_claimed_at = now();
                $progress->completion_count = (int) $progress->completion_count + 1;
                $completed = true;
                $reward = (array) ($entry->reward ?? []);
            }
            $progress->save();
            $completionCount = (int) $progress->completion_count;

            return $progress->fresh();
        });
        CollectionProgressRecorded::dispatch((string) $collection->getKey(), $actorId, $entryKey, (int) $progress->quantity, $completed);
        if ($completed && $reward !== []) {
            CollectionRewardGranted::dispatch((string) $collection->getKey(), $actorId, $entryKey, $reward, $completionCount);
        }

        return $progress;
    }
}
