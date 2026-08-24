<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\ModerationAndAnalytics\Events\ModerationAndAnalyticsDefined;
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
        $allowed = ['report', 'sanction', 'appeal', 'telemetry', 'funnel', 'balance', 'economy', 'fraud', 'health'];
        if (! in_array($kind, $allowed, true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported moderation or analytics kind.']);
        }
        if ($idempotencyKey !== null && ($existing = ModerationAndAnalyticsRecord::query()->where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }

        return ModerationAndAnalyticsRecord::query()->create(['id' => (string) Str::uuid(), 'name' => $name, 'kind' => $kind, 'actor_id' => $actorId, 'target_id' => $targetId, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'severity' => $data['severity'] ?? null, 'value' => $data['value'] ?? null, 'data' => $data, 'idempotency_key' => $idempotencyKey, 'status' => $kind === 'report' || $kind === 'appeal' ? 'open' : 'recorded']);
    }

    public function resolve(ModerationAndAnalyticsRecord $record, string $status = 'resolved'): ModerationAndAnalyticsRecord
    {
        if (! in_array($record->kind, ['report', 'sanction', 'appeal', 'fraud', 'health'], true)) {
            throw ValidationException::withMessages(['kind' => 'This record is not resolvable.']);
        }
        if (! in_array($status, ['resolved', 'dismissed', 'active', 'revoked'], true)) {
            throw ValidationException::withMessages(['status' => 'Invalid resolution status.']);
        }
        $record->update(['status' => $status, 'resolved_at' => now()]);

        return $record->fresh();
    }

    public function issueSanction(string $actorId, string $targetId, string $name, array $data = []): ModerationAndAnalyticsRecord
    {
        return $this->record('sanction', $name, $actorId, $targetId, $data + ['severity' => 'moderate']);
    }

    public function submitAppeal(string $actorId, string $targetId, string $reason, array $data = []): ModerationAndAnalyticsRecord
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'An appeal reason is required.']);
        }

        return $this->record('appeal', 'appeal', $actorId, $targetId, $data + ['reason' => $reason]);
    }
}
