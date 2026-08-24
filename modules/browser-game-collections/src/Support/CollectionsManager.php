<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    public function record(string $actorId, CollectionsRecord $collection, string $entryKey, int $quantity = 1): CollectionProgress
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be positive.']);
        }

        return DB::transaction(function () use ($actorId, $collection, $entryKey, $quantity): CollectionProgress {
            $entry = $collection->entries()->where('entry_key', $entryKey)->firstOrFail();
            $progress = CollectionProgress::query()->firstOrCreate(['actor_id' => $actorId, 'collection_id' => $collection->getKey(), 'entry_key' => $entryKey], ['quantity' => 0]);
            if ($progress->completed_at === null) {
                $progress->quantity = min($entry->required_quantity, $progress->quantity + $quantity);
                if ($progress->quantity >= $entry->required_quantity) {
                    $progress->completed_at = now();
                }
                $progress->save();
            }

            return $progress->fresh();
        });
    }
}
