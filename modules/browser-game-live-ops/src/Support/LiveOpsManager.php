<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\LiveOps\Events\LiveOpsDefined;
use Liberu\BrowserGame\LiveOps\Models\LiveOpsClaim;
use Liberu\BrowserGame\LiveOps\Models\LiveOpsRecord;

final class LiveOpsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): LiveOpsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): LiveOpsRecord => LiveOpsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        LiveOpsDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function create(string $name, string $kind, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): LiveOpsRecord
    {
        $allowed = ['daily_activity', 'event', 'season', 'schedule', 'announcement', 'grant'];
        if (! in_array($kind, $allowed, true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported Live Ops kind.']);
        }
        if ($idempotencyKey !== null && ($existing = LiveOpsRecord::query()->where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }
        $record = $this->define($name, $data, $tenantId, $teamId);
        $record->forceFill(['kind' => $kind, 'status' => 'draft', 'idempotency_key' => $idempotencyKey, 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null])->save();

        return $record->fresh();
    }

    public function publish(LiveOpsRecord $record): LiveOpsRecord
    {
        if (! in_array($record->status, ['draft', 'paused'], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or paused records can be published.']);
        }
        if ($record->ends_at !== null && $record->starts_at !== null && $record->ends_at->lessThanOrEqualTo($record->starts_at)) {
            throw ValidationException::withMessages(['ends_at' => 'The end must be after the start.']);
        }
        $record->update(['status' => 'published']);

        return $record->fresh();
    }

    public function claim(string $actorId, LiveOpsRecord $record, string $claimKey = 'default'): LiveOpsClaim
    {
        if ($record->status !== 'published') {
            throw ValidationException::withMessages(['status' => 'This Live Ops activity is not available.']);
        }
        if ($record->starts_at?->isFuture() || $record->ends_at?->isPast()) {
            throw ValidationException::withMessages(['availability' => 'This activity is outside its active window.']);
        }

        return DB::transaction(function () use ($actorId, $record, $claimKey): LiveOpsClaim {
            $existing = LiveOpsClaim::query()->where(['actor_id' => $actorId, 'live_ops_id' => $record->getKey(), 'claim_key' => $claimKey])->first();
            if ($existing !== null) {
                return $existing;
            }

            return LiveOpsClaim::query()->create(['actor_id' => $actorId, 'live_ops_id' => $record->getKey(), 'claim_key' => $claimKey, 'status' => 'claimed', 'grant' => $record->data['grant'] ?? null]);
        });
    }

    public function rollback(LiveOpsRecord $record, string $actorId, string $reason): LiveOpsRecord
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A rollback reason is required.']);
        }

        return DB::transaction(function () use ($record, $actorId, $reason): LiveOpsRecord {
            $from = (int) $record->version;
            $record->rollbacks()->create(['from_version' => $from, 'to_version' => $from + 1, 'actor_id' => $actorId, 'reason' => $reason, 'snapshot' => $record->data]);
            $record->update(['version' => $from + 1, 'status' => 'rolled_back']);

            return $record->fresh();
        });
    }
}
