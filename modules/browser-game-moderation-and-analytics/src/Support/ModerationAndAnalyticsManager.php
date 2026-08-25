<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\ModerationAndAnalytics\Events\ModerationAndAnalyticsDefined;
use Liberu\BrowserGame\ModerationAndAnalytics\Events\ModerationAndAnalyticsRecorded;
use Liberu\BrowserGame\ModerationAndAnalytics\Events\ModerationAndAnalyticsResolved;
use Liberu\BrowserGame\ModerationAndAnalytics\Models\ModerationAndAnalyticsRecord;

final class ModerationAndAnalyticsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): ModerationAndAnalyticsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): ModerationAndAnalyticsRecord => ModerationAndAnalyticsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        ModerationAndAnalyticsDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function record(string $kind, string $name, ?string $actorId = null, ?string $targetId = null, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }
        $allowed = ['report', 'sanction', 'appeal', 'telemetry', 'funnel', 'balance', 'economy', 'fraud', 'health'];
        if (! in_array($kind, $allowed, true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported moderation or analytics kind.']);
        }
        $record = DB::transaction(function () use ($kind, $name, $actorId, $targetId, $data, $tenantId, $teamId, $idempotencyKey): ModerationAndAnalyticsRecord {
            if ($idempotencyKey !== null && ($existing = ModerationAndAnalyticsRecord::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if ((string) $existing->actor_id !== (string) $actorId || (string) $existing->team_id !== (string) $teamId) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another moderation or analytics operation.']);
                }

                return $existing;
            }

            return ModerationAndAnalyticsRecord::query()->create(['id' => (string) Str::uuid(), 'name' => $name, 'kind' => $kind, 'actor_id' => $actorId, 'target_id' => $targetId, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'severity' => $data['severity'] ?? null, 'value' => $data['value'] ?? null, 'data' => $data, 'idempotency_key' => $idempotencyKey, 'status' => $kind === 'report' || $kind === 'appeal' ? 'open' : 'recorded']);
        });

        $record = $record->fresh();
        ModerationAndAnalyticsRecorded::dispatch((string) $record->getKey(), $kind, $actorId, $targetId);

        return $record;
    }

    public function resolve(ModerationAndAnalyticsRecord $record, string $status = 'resolved'): ModerationAndAnalyticsRecord
    {
        if (! in_array($record->kind, ['report', 'sanction', 'appeal', 'fraud', 'health'], true)) {
            throw ValidationException::withMessages(['kind' => 'This record is not resolvable.']);
        }
        if (! in_array($status, ['resolved', 'dismissed', 'active', 'revoked'], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid resolution status.']);
        }
        $updated = DB::transaction(function () use ($record, $status): ModerationAndAnalyticsRecord {
            $record = ModerationAndAnalyticsRecord::query()->lockForUpdate()->findOrFail($record->getKey());
            if (! in_array($record->kind, ['report', 'sanction', 'appeal', 'fraud', 'health'], true)) {
                throw ValidationException::withMessages(['kind' => 'This record is not resolvable.']);
            }
            $record->update(['status' => $status, 'resolved_at' => now()]);

            return $record->fresh();
        });
        ModerationAndAnalyticsResolved::dispatch((string) $updated->getKey(), $status);

        return $updated;
    }

    public function issueSanction(string $actorId, string $targetId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        return $this->record('sanction', $name, $actorId, $targetId, $data + ['severity' => 'moderate'], $tenantId, $teamId, $idempotencyKey);
    }

    public function submitAppeal(string $actorId, string $targetId, string $reason, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'An appeal reason is required.']);
        }

        return $this->record('appeal', 'appeal', $actorId, $targetId, $data + ['reason' => $reason], $tenantId, $teamId, $idempotencyKey);
    }

    public function recordTelemetry(string $actorId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        return $this->record('telemetry', $name, $actorId, null, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function recordFunnel(string $actorId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        return $this->record('funnel', $name, $actorId, null, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function recordBalance(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        return $this->record('balance', $name, null, null, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function recordEconomy(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        return $this->record('economy', $name, null, null, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function recordFraud(string $actorId, string $targetId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        return $this->record('fraud', $name, $actorId, $targetId, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function recordHealth(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): ModerationAndAnalyticsRecord
    {
        return $this->record('health', $name, null, null, $data, $tenantId, $teamId, $idempotencyKey);
    }
}
