<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Social\Events\SocialDefined;
use Liberu\BrowserGame\Social\Models\SocialActivity;
use Liberu\BrowserGame\Social\Models\SocialMembership;
use Liberu\BrowserGame\Social\Models\SocialRecord;

final class SocialManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): SocialRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): SocialRecord => SocialRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        SocialDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function create(string $name, string $kind, string $ownerId, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null, ?string $targetId = null): SocialRecord
    {
        if (trim($name) === '' || trim($ownerId) === '') {
            throw ValidationException::withMessages(['social' => 'A name and owner are required.']);
        }
        $allowed = ['friend', 'party', 'chat', 'mail', 'guild', 'alliance', 'report'];
        if (! in_array($kind, $allowed, true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported social kind.']);
        }
        $record = DB::transaction(function () use ($name, $kind, $ownerId, $data, $tenantId, $teamId, $idempotencyKey, $targetId): SocialRecord {
            if ($idempotencyKey !== null && ($existing = SocialRecord::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if ((string) $existing->owner_id !== $ownerId || (string) $existing->tenant_id !== (string) $tenantId || (string) $existing->team_id !== (string) $teamId) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another social operation.']);
                }

                return $existing;
            }
            $record = SocialRecord::query()->create(['id' => (string) Str::uuid(), 'name' => $name, 'kind' => $kind, 'owner_id' => $ownerId, 'target_id' => $targetId, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'data' => $data, 'idempotency_key' => $idempotencyKey, 'status' => 'active']);
            $record->memberships()->create(['actor_id' => $ownerId, 'role' => 'owner', 'permissions' => ['manage' => true]]);
            SocialActivity::query()->create(['actor_id' => $ownerId, 'kind' => $kind.'.created', 'target_id' => (string) $record->getKey(), 'data' => $data]);

            return $record;
        });

        return $record->fresh('memberships');
    }

    public function createFriendRequest(string $actorId, string $targetId, ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        if (trim($targetId) === '' || $actorId === $targetId) {
            throw ValidationException::withMessages(['target_id' => 'A different friend target is required.']);
        }

        return $this->create('Friend request', 'friend', $actorId, ['requested_by' => $actorId], $tenantId, $teamId, $idempotencyKey, $targetId);
    }

    public function respondToFriendRequest(SocialRecord $request, string $actorId, string $status): SocialRecord
    {
        if ($request->kind !== 'friend' || (string) $request->target_id !== $actorId || $request->status !== 'active') {
            throw ValidationException::withMessages(['friend' => 'This friend request is not available to you.']);
        }
        if (! in_array($status, ['accepted', 'declined'], true)) {
            throw ValidationException::withMessages(['status' => 'A friend request can only be accepted or declined.']);
        }

        return DB::transaction(function () use ($request, $actorId, $status): SocialRecord {
            $request = SocialRecord::query()->lockForUpdate()->findOrFail($request->getKey());
            if ($request->kind !== 'friend' || (string) $request->target_id !== $actorId || $request->status !== 'active') {
                throw ValidationException::withMessages(['friend' => 'This friend request is not available to you.']);
            }
            $request->update(['status' => $status]);
            SocialActivity::query()->create(['actor_id' => $actorId, 'kind' => 'friend.'.$status, 'target_id' => (string) $request->getKey()]);

            return $request->fresh();
        });
    }

    public function createParty(string $ownerId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        return $this->create($name, 'party', $ownerId, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createChat(string $ownerId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        return $this->create($name, 'chat', $ownerId, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createMail(string $ownerId, string $recipientId, string $body, ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        if (trim($recipientId) === '' || trim($body) === '') {
            throw ValidationException::withMessages(['mail' => 'A recipient and message body are required.']);
        }

        return $this->create('Mail', 'mail', $ownerId, ['recipient_id' => $recipientId, 'body' => $body], $tenantId, $teamId, $idempotencyKey, $recipientId);
    }

    public function createGuild(string $ownerId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        return $this->create($name, 'guild', $ownerId, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createAlliance(string $ownerId, string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        return $this->create($name, 'alliance', $ownerId, $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function updatePermissions(SocialRecord $record, string $actorId, string $memberId, array $permissions): SocialMembership
    {
        if (! in_array($record->kind, ['party', 'guild', 'alliance', 'chat'], true)) {
            throw ValidationException::withMessages(['kind' => 'This social record does not have member permissions.']);
        }
        if (! $record->memberships()->where('actor_id', $actorId)->where('status', 'active')->whereJsonContains('permissions->manage', true)->exists()) {
            throw ValidationException::withMessages(['authorization' => 'Only members with manage permission can update permissions.']);
        }

        $membership = $record->memberships()->where('actor_id', $memberId)->where('status', 'active')->firstOrFail();
        $membership->update(['permissions' => $permissions]);
        SocialActivity::query()->create(['actor_id' => $actorId, 'kind' => $record->kind.'.permissions_updated', 'target_id' => (string) $membership->getKey(), 'data' => ['member_id' => $memberId, 'permissions' => $permissions]]);

        return $membership->fresh();
    }

    public function recordActivity(string $actorId, string $kind, ?string $targetId = null, array $data = []): SocialActivity
    {
        if (trim($kind) === '') {
            throw ValidationException::withMessages(['kind' => 'An activity kind is required.']);
        }

        return SocialActivity::query()->create(['actor_id' => $actorId, 'kind' => $kind, 'target_id' => $targetId, 'data' => $data]);
    }

    public function addMember(SocialRecord $record, string $actorId, string $role = 'member', array $permissions = [], ?string $requestedBy = null): SocialMembership
    {
        if (! in_array($record->kind, ['party', 'guild', 'alliance', 'chat'], true)) {
            throw ValidationException::withMessages(['kind' => 'This social record does not accept members.']);
        }
        if ($requestedBy !== null && ! $record->memberships()->where('actor_id', $requestedBy)->where('status', 'active')->whereJsonContains('permissions->manage', true)->exists()) {
            throw ValidationException::withMessages(['authorization' => 'Only members with manage permission can add members.']);
        }
        $membership = $record->memberships()->updateOrCreate(['actor_id' => $actorId], ['role' => $role, 'status' => 'active', 'permissions' => $permissions]);
        SocialActivity::query()->create(['actor_id' => $actorId, 'kind' => $record->kind.'.joined', 'target_id' => (string) $record->getKey()]);

        return $membership;
    }

    public function send(string $actorId, SocialRecord $record, string $body): SocialRecord
    {
        if (! in_array($record->kind, ['chat', 'mail'], true) || trim($body) === '') {
            throw ValidationException::withMessages(['body' => 'A valid message is required.']);
        }
        if ($record->kind === 'chat' && ! $record->memberships()->where('actor_id', $actorId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['authorization' => 'You are not a member of this conversation.']);
        }
        $message = DB::transaction(function () use ($actorId, $record, $body): SocialRecord {
            $message = SocialRecord::query()->create(['id' => (string) Str::uuid(), 'name' => 'message', 'kind' => $record->kind, 'owner_id' => $actorId, 'target_id' => (string) $record->getKey(), 'tenant_id' => $record->tenant_id, 'team_id' => $record->team_id, 'body' => $body, 'status' => 'sent', 'data' => []]);
            SocialActivity::query()->create(['actor_id' => $actorId, 'kind' => $record->kind.'.sent', 'target_id' => (string) $message->getKey()]);

            return $message;
        });

        return $message;
    }

    public function report(string $actorId, string $targetId, string $reason, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A report reason is required.']);
        }

        return DB::transaction(function () use ($actorId, $targetId, $reason, $data, $tenantId, $teamId, $idempotencyKey): SocialRecord {
            if ($idempotencyKey !== null && ($existing = SocialRecord::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if ((string) $existing->owner_id !== $actorId || (string) $existing->tenant_id !== (string) $tenantId || (string) $existing->team_id !== (string) $teamId) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another social report.']);
                }

                return $existing;
            }

            return SocialRecord::query()->create(['id' => (string) Str::uuid(), 'name' => 'report', 'kind' => 'report', 'owner_id' => $actorId, 'target_id' => $targetId, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'body' => $reason, 'status' => 'open', 'idempotency_key' => $idempotencyKey, 'data' => $data]);
        });
    }
}
