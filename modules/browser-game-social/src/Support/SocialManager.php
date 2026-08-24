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

    public function create(string $name, string $kind, string $ownerId, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): SocialRecord
    {
        $allowed = ['friend', 'party', 'chat', 'mail', 'guild', 'alliance', 'report'];
        if (! in_array($kind, $allowed, true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported social kind.']);
        }
        if ($idempotencyKey !== null && ($existing = SocialRecord::query()->where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }
        $record = SocialRecord::query()->create(['id' => (string) Str::uuid(), 'name' => $name, 'kind' => $kind, 'owner_id' => $ownerId, 'tenant_id' => $tenantId, 'team_id' => $teamId, 'data' => $data, 'idempotency_key' => $idempotencyKey, 'status' => 'active']);
        $record->memberships()->create(['actor_id' => $ownerId, 'role' => 'owner', 'permissions' => ['manage' => true]]);
        SocialActivity::query()->create(['actor_id' => $ownerId, 'kind' => $kind.'.created', 'target_id' => (string) $record->getKey(), 'data' => $data]);

        return $record->fresh('memberships');
    }

    public function addMember(SocialRecord $record, string $actorId, string $role = 'member', array $permissions = []): SocialMembership
    {
        if (! in_array($record->kind, ['party', 'guild', 'alliance', 'chat'], true)) {
            throw ValidationException::withMessages(['kind' => 'This social record does not accept members.']);
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
        $message = SocialRecord::query()->create(['id' => (string) Str::uuid(), 'name' => 'message', 'kind' => $record->kind, 'owner_id' => $actorId, 'target_id' => (string) $record->getKey(), 'body' => $body, 'status' => 'sent', 'data' => []]);
        SocialActivity::query()->create(['actor_id' => $actorId, 'kind' => $record->kind.'.sent', 'target_id' => (string) $message->getKey()]);

        return $message;
    }

    public function report(string $actorId, string $targetId, string $reason, array $data = []): SocialRecord
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A report reason is required.']);
        }

        return SocialRecord::query()->create(['id' => (string) Str::uuid(), 'name' => 'report', 'kind' => 'report', 'owner_id' => $actorId, 'target_id' => $targetId, 'body' => $reason, 'status' => 'open', 'data' => $data]);
    }
}
