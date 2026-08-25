<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\LiveOps\Events\LiveOpsClaimed;
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
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }
        $allowed = ['daily_activity', 'event', 'season', 'schedule', 'announcement', 'grant'];
        if (! in_array($kind, $allowed, true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported Live Ops kind.']);
        }
        $record = DB::transaction(function () use ($name, $kind, $data, $tenantId, $teamId, $idempotencyKey): LiveOpsRecord {
            if ($idempotencyKey !== null && ($existing = LiveOpsRecord::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if ((string) $existing->tenant_id !== (string) $tenantId || (string) $existing->team_id !== (string) $teamId) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another Live Ops scope.']);
                }

                return $existing;
            }
            $record = LiveOpsRecord::query()->create([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'data' => $data,
                'tenant_id' => $tenantId,
                'team_id' => $teamId,
                'kind' => $kind,
                'status' => 'draft',
                'idempotency_key' => $idempotencyKey,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]);
            LiveOpsDefined::dispatch((string) $record->getKey());

            return $record;
        });

        return $record->fresh();
    }

    public function createDailyActivity(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): LiveOpsRecord
    {
        return $this->create($name, 'daily_activity', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createEvent(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): LiveOpsRecord
    {
        return $this->create($name, 'event', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createSeason(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): LiveOpsRecord
    {
        return $this->create($name, 'season', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createSchedule(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): LiveOpsRecord
    {
        return $this->create($name, 'schedule', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createAnnouncement(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): LiveOpsRecord
    {
        return $this->create($name, 'announcement', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createGrant(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): LiveOpsRecord
    {
        return $this->create($name, 'grant', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function publish(LiveOpsRecord $record): LiveOpsRecord
    {
        if (! in_array($record->status, ['draft', 'paused'], true)) {
            throw ValidationException::withMessages(['status' => 'Only draft or paused records can be published.']);
        }
        if ($record->ends_at !== null && $record->starts_at !== null && $record->ends_at->lessThanOrEqualTo($record->starts_at)) {
            throw ValidationException::withMessages(['ends_at' => 'The end must be after the start.']);
        }

        return DB::transaction(function () use ($record): LiveOpsRecord {
            $record = LiveOpsRecord::query()->lockForUpdate()->findOrFail($record->getKey());
            if (! in_array($record->status, ['draft', 'paused'], true)) {
                throw ValidationException::withMessages(['status' => 'Only draft or paused records can be published.']);
            }
            if ($record->ends_at !== null && $record->starts_at !== null && $record->ends_at->lessThanOrEqualTo($record->starts_at)) {
                throw ValidationException::withMessages(['ends_at' => 'The end must be after the start.']);
            }
            $record->update(['status' => 'published']);

            return $record->fresh();
        });
    }

    public function claim(string $actorId, LiveOpsRecord $record, string $claimKey = 'default'): LiveOpsClaim
    {
        $this->required($actorId, 'actor_id');
        $this->required($claimKey, 'claim_key');

        $created = false;
        $claim = DB::transaction(function () use ($actorId, $record, $claimKey, &$created): LiveOpsClaim {
            $record = LiveOpsRecord::query()->lockForUpdate()->findOrFail($record->getKey());
            $this->assertAvailable($record);
            $existing = LiveOpsClaim::query()->where([
                'actor_id' => $actorId,
                'live_ops_id' => $record->getKey(),
                'claim_key' => $claimKey,
            ])->first();
            if ($existing !== null) {
                return $existing;
            }

            $created = true;

            return LiveOpsClaim::query()->create([
                'actor_id' => $actorId,
                'live_ops_id' => $record->getKey(),
                'claim_key' => $claimKey,
                'status' => 'claimed',
                'grant' => $record->data['grant'] ?? null,
            ]);
        });
        if ($created) {
            LiveOpsClaimed::dispatch((string) $record->getKey(), $actorId, (string) $claim->getKey(), $claimKey);
        }

        return $claim;
    }

    /**
     * Claim a published daily activity once per calendar day and return its
     * current streak in the claim grant metadata.
     */
    public function claimDaily(string $actorId, LiveOpsRecord $record, ?string $timezone = null): LiveOpsClaim
    {
        if ($record->kind !== 'daily_activity') {
            throw ValidationException::withMessages(['kind' => 'The record is not a daily activity.']);
        }
        $claimKey = now($timezone ?: config('app.timezone', 'UTC'))->toDateString();
        $claim = $this->claim($actorId, $record, $claimKey);
        if ($claim->getAttribute('grant') !== null && array_key_exists('streak', (array) $claim->grant)) {
            return $claim;
        }

        $streak = $this->dailyStreak($actorId, $record, $claimKey, $timezone, false);
        $grant = array_merge((array) ($claim->grant ?? $record->data['grant'] ?? []), ['streak' => $streak, 'claim_date' => $claimKey]);
        $claim->update(['grant' => $grant]);

        return $claim->refresh();
    }

    /** @return array{claim_key: string, claimed: bool, streak: int, grant: array<mixed>} */
    public function dailyStatus(string $actorId, LiveOpsRecord $record, ?string $timezone = null): array
    {
        if ($record->kind !== 'daily_activity') {
            throw ValidationException::withMessages(['kind' => 'The record is not a daily activity.']);
        }
        $claimKey = now($timezone ?: config('app.timezone', 'UTC'))->toDateString();
        $claim = LiveOpsClaim::query()->where(['actor_id' => $actorId, 'live_ops_id' => $record->getKey(), 'claim_key' => $claimKey])->first();

        return [
            'claim_key' => $claimKey,
            'claimed' => $claim !== null,
            'streak' => $claim === null ? $this->dailyStreak($actorId, $record, $claimKey, $timezone) : (int) (($claim->grant ?? [])['streak'] ?? 0),
            'grant' => (array) ($record->data['grant'] ?? []),
        ];
    }

    private function dailyStreak(string $actorId, LiveOpsRecord $record, string $claimKey, ?string $timezone, bool $includeCurrent = true): int
    {
        $date = CarbonImmutable::parse($claimKey, $timezone ?: config('app.timezone', 'UTC'));
        if (! $includeCurrent) {
            $date = $date->subDay();
        }
        $streak = 0;
        while (LiveOpsClaim::query()->where([
            'actor_id' => $actorId,
            'live_ops_id' => $record->getKey(),
            'claim_key' => $date->toDateString(),
        ])->exists()) {
            $streak++;
            $date = $date->subDay();
        }

        return $streak + 1;
    }

    private function assertAvailable(LiveOpsRecord $record): void
    {
        if ($record->status !== 'published') {
            throw ValidationException::withMessages(['status' => 'This Live Ops activity is not available.']);
        }
        if ($record->starts_at?->isFuture() || $record->ends_at?->isPast()) {
            throw ValidationException::withMessages(['availability' => 'This activity is outside its active window.']);
        }

    }

    private function required(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw ValidationException::withMessages([$field => 'A value is required.']);
        }
    }

    public function rollback(LiveOpsRecord $record, string $actorId, string $reason): LiveOpsRecord
    {
        $this->required($actorId, 'actor_id');
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
