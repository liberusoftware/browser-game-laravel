<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Accounts\Events\AccountBanChanged;
use Liberu\BrowserGame\Accounts\Events\AccountEmailVerified;
use Liberu\BrowserGame\Accounts\Events\AccountLifecycleChanged;
use Liberu\BrowserGame\Accounts\Events\AccountRecoveryIssued;
use Liberu\BrowserGame\Accounts\Events\AccountsDefined;
use Liberu\BrowserGame\Accounts\Events\AccountSessionRevoked;
use Liberu\BrowserGame\Accounts\Models\AccountBan;
use Liberu\BrowserGame\Accounts\Models\AccountPrivacy;
use Liberu\BrowserGame\Accounts\Models\AccountRecovery;
use Liberu\BrowserGame\Accounts\Models\AccountSession;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;

final class AccountsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): AccountsRecord
    {
        $this->requiredText($name, 'name');
        $email = isset($data['email']) ? $this->email((string) $data['email']) : null;
        $username = isset($data['username']) ? $this->username((string) $data['username']) : null;
        $this->assertIdentityAvailable($email, $username, $tenantId);

        $record = DB::transaction(fn (): AccountsRecord => AccountsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
            'data' => array_diff_key($data, array_flip(['email', 'username', 'password', 'token', 'secret', 'refresh_token'])),
        ]));
        AccountsDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function updateIdentity(AccountsRecord $account, string $name, ?string $email = null, ?string $username = null): AccountsRecord
    {
        $this->requiredText($name, 'name');
        $email = $email === null ? null : $this->email($email);
        $username = $username === null ? null : $this->username($username);
        $this->assertIdentityAvailable($email, $username, $account->tenant_id, $account);
        $account->update([
            'name' => $name,
            'email' => $email,
            'username' => $username,
        ]);

        return $account->refresh();
    }

    public function verifyEmail(AccountsRecord $account): AccountsRecord
    {
        if ($account->email === null) {
            throw ValidationException::withMessages(['email' => 'An email address is required before verification.']);
        }
        if ($account->email_verified_at === null) {
            $account->update(['email_verified_at' => now()]);
            AccountEmailVerified::dispatch((string) $account->getKey(), (string) $account->email);
        }

        return $account->refresh();
    }

    public function setAgeRegionPolicy(AccountsRecord $account, ?int $birthYear, ?string $region, bool $verified): AccountsRecord
    {
        if ($birthYear !== null && ($birthYear < 1900 || $birthYear > (int) now()->format('Y'))) {
            throw ValidationException::withMessages(['birth_year' => 'The birth year is invalid.']);
        }
        if ($verified && $birthYear !== null && ((int) now()->format('Y') - $birthYear) < (int) config('browser-game.accounts.minimum_age', 13)) {
            throw ValidationException::withMessages(['age_verified' => 'This account does not meet the minimum age.']);
        }

        $account->update(['birth_year' => $birthYear, 'region' => $region, 'age_verified' => $verified]);

        return $account->refresh();
    }

    public function suspend(AccountsRecord $account, ?string $actorId = null): AccountsRecord
    {
        return $this->changeStatus($account, 'suspended', $actorId);
    }

    public function close(AccountsRecord $account, ?string $actorId = null): AccountsRecord
    {
        return $this->changeStatus($account, 'closed', $actorId);
    }

    public function reactivate(AccountsRecord $account, ?string $actorId = null): AccountsRecord
    {
        return $this->changeStatus($account, 'active', $actorId);
    }

    /** @return array{session: AccountSession, token: string} */
    public function createSession(AccountsRecord $account, ?string $ipAddress = null, ?string $userAgent = null, ?int $days = 30): array
    {
        if ($account->status !== 'active' || $this->isBanned($account)) {
            throw ValidationException::withMessages(['account' => 'The account cannot create a session.']);
        }
        $token = Str::random(80);
        $session = $account->sessions()->create([
            'token_hash' => hash('sha512', $token),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'last_seen_at' => now(),
            'expires_at' => $days === null ? null : now()->addDays(max(1, $days)),
        ]);

        return ['session' => $session, 'token' => $token];
    }

    public function revokeSession(AccountsRecord $account, AccountSession|int $session, ?string $actorId = null): AccountSession
    {
        $record = $account->sessions()->whereKey($session instanceof AccountSession ? $session->getKey() : $session)->firstOrFail();
        $record->update(['revoked_at' => now()]);
        AccountSessionRevoked::dispatch((string) $account->getKey(), (int) $record->getKey(), $actorId);

        return $record->refresh();
    }

    public function revokeAllSessions(AccountsRecord $account, ?string $actorId = null): int
    {
        $sessions = $account->sessions()->whereNull('revoked_at')->get();
        foreach ($sessions as $session) {
            $this->revokeSession($account, $session, $actorId);
        }

        return $sessions->count();
    }

    /** @return array{recovery: AccountRecovery, token: string} */
    public function issueRecovery(AccountsRecord $account, int $minutes = 30): array
    {
        $token = Str::random(80);
        $account->recoveries()->whereNull('used_at')->update(['used_at' => now()]);
        $recovery = $account->recoveries()->create([
            'token_hash' => hash('sha512', $token),
            'expires_at' => now()->addMinutes(max(1, $minutes)),
        ]);
        AccountRecoveryIssued::dispatch((string) $account->getKey(), (int) $recovery->getKey());

        return ['recovery' => $recovery, 'token' => $token];
    }

    public function consumeRecovery(string $token): ?AccountsRecord
    {
        return DB::transaction(function () use ($token): ?AccountsRecord {
            $recovery = AccountRecovery::query()->where('token_hash', hash('sha512', $token))
                ->whereNull('used_at')->where('expires_at', '>', now())
                ->where('attempts', '<', (int) config('browser-game.accounts.maximum_recovery_attempts', 5))
                ->lockForUpdate()->first();
            if ($recovery === null) {
                return null;
            }

            $recovery->update(['attempts' => $recovery->attempts + 1, 'used_at' => now()]);

            return $recovery->account;
        });
    }

    public function resolveSession(string $token): ?AccountSession
    {
        $session = AccountSession::query()->with('account')
            ->where('token_hash', hash('sha512', $token))
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
        if ($session === null || $session->account->status !== 'active' || $this->isBanned($session->account)) {
            return null;
        }
        $session->update(['last_seen_at' => now()]);

        return $session->refresh();
    }

    public function ban(AccountsRecord $account, string $reason, ?string $endsAt = null, ?string $actorId = null): AccountBan
    {
        $this->requiredText($reason, 'reason');
        $ban = DB::transaction(function () use ($account, $reason, $endsAt, $actorId): AccountBan {
            $ban = $account->bans()->create([
                'reason' => $reason,
                'scope' => 'account',
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'issued_by' => $actorId,
            ]);
            $account->update(['status' => 'suspended', 'suspended_at' => now()]);

            return $ban;
        });
        AccountBanChanged::dispatch((string) $account->getKey(), (int) $ban->getKey(), true, $actorId);

        return $ban;
    }

    public function liftBan(AccountsRecord $account, AccountBan|int $ban, ?string $actorId = null): AccountBan
    {
        $record = $account->bans()->whereKey($ban instanceof AccountBan ? $ban->getKey() : $ban)->firstOrFail();
        $record->update(['revoked_at' => now()]);
        if (! $this->isBanned($account->fresh())) {
            $account->update(['status' => 'active', 'suspended_at' => null]);
        }
        AccountBanChanged::dispatch((string) $account->getKey(), (int) $record->getKey(), false, $actorId);

        return $record->refresh();
    }

    public function isBanned(AccountsRecord $account): bool
    {
        return $account->bans()->whereNull('revoked_at')->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))->exists();
    }

    public function updatePrivacy(AccountsRecord $account, string $visibility, bool $marketing, bool $analytics): AccountPrivacy
    {
        if (! in_array($visibility, ['private', 'friends', 'public'], true)) {
            throw ValidationException::withMessages(['profile_visibility' => 'Profile visibility is invalid.']);
        }

        return AccountPrivacy::query()->updateOrCreate(
            ['account_id' => $account->getKey()],
            ['profile_visibility' => $visibility, 'marketing_consent' => $marketing, 'analytics_consent' => $analytics],
        );
    }

    public function requestDeletion(AccountsRecord $account): AccountPrivacy
    {
        return AccountPrivacy::query()->updateOrCreate(
            ['account_id' => $account->getKey()],
            ['deletion_requested_at' => now()],
        );
    }

    public function completeDeletion(AccountsRecord $account, ?string $actorId = null): AccountPrivacy
    {
        $privacy = DB::transaction(function () use ($account): AccountPrivacy {
            $privacy = AccountPrivacy::query()->whereKey($account->getKey())->lockForUpdate()->firstOrCreate(
                ['account_id' => $account->getKey()],
                ['deletion_requested_at' => now()],
            );
            if ($privacy->deletion_completed_at === null) {
                $privacy->update(['deletion_requested_at' => $privacy->deletion_requested_at ?? now(), 'deletion_completed_at' => now()]);
                $account->update(['name' => 'Deleted account', 'email' => null, 'username' => null, 'status' => 'closed', 'closed_at' => now()]);
                $account->sessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }

            return $privacy->refresh();
        });
        AccountLifecycleChanged::dispatch((string) $account->getKey(), 'deleted', $actorId);

        return $privacy;
    }

    private function changeStatus(AccountsRecord $account, string $status, ?string $actorId): AccountsRecord
    {
        if ($status === 'active' && $this->isBanned($account)) {
            throw ValidationException::withMessages(['account' => 'A banned account cannot be reactivated.']);
        }
        $account->update([
            'status' => $status,
            'suspended_at' => $status === 'suspended' ? now() : null,
            'closed_at' => $status === 'closed' ? now() : null,
        ]);
        AccountLifecycleChanged::dispatch((string) $account->getKey(), $status, $actorId);

        return $account->refresh();
    }

    private function email(string $email): string
    {
        $email = trim($email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => 'A valid email address is required.']);
        }

        return $email;
    }

    private function username(string $username): string
    {
        $username = trim($username);
        if (! preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            throw ValidationException::withMessages(['username' => 'Username must be 3-50 characters and use letters, numbers, dots, dashes, or underscores.']);
        }

        return $username;
    }

    private function requiredText(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw ValidationException::withMessages([$field => 'A value is required.']);
        }
    }

    private function assertIdentityAvailable(?string $email, ?string $username, ?string $tenantId, ?AccountsRecord $except = null): void
    {
        $query = AccountsRecord::query()->where('tenant_id', $tenantId)
            ->when($except, fn ($builder) => $builder->where($builder->getModel()->getKeyName(), '!=', $except->getKey()));
        if (($email !== null && (clone $query)->where('email', $email)->exists())
            || ($username !== null && (clone $query)->where('username', $username)->exists())) {
            throw ValidationException::withMessages(['identity' => 'The email or username is already registered in this tenant.']);
        }
    }
}
