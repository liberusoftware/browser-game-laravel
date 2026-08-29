<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Accounts\Models\AccountBan;
use Liberu\BrowserGame\Accounts\Models\AccountPrivacy;
use Liberu\BrowserGame\Accounts\Models\AccountSession;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;
use Liberu\BrowserGame\Accounts\Queries\AccountsQuery;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;

final class AccountsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        $items = app(AccountsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey())->latest()->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

        return response()->json($items->through(fn (Model $item): array => $this->resource($item)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'data' => ['array'],
        ]);
        $team = $request->user()?->currentTeam;
        $account = app(AccountsManager::class)->define($data['name'], array_merge($data['data'] ?? [], ['email' => $data['email'] ?? null, 'username' => $data['username'] ?? null]), $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());

        return response()->json(['data' => $this->resource($account)], 201);
    }

    public function show(Request $request, AccountsRecord $account): JsonResponse
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        $account = app(AccountsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())
            ->whereKey($account->getKey())
            ->firstOrFail();

        return response()->json(['data' => $this->resource($account)]);
    }

    public function update(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/'],
        ]);
        $updated = app(AccountsManager::class)->updateIdentity($account, $data['name'], $data['email'] ?? null, $data['username'] ?? null);

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function ageRegion(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $data = $request->validate([
            'birth_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'region' => ['nullable', 'string', 'max:20'],
            'age_verified' => ['required', 'boolean'],
        ]);
        $updated = app(AccountsManager::class)->setAgeRegionPolicy($account, $data['birth_year'] ?? null, $data['region'] ?? null, (bool) $data['age_verified']);

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function verifyEmail(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);

        return response()->json(['data' => $this->resource(app(AccountsManager::class)->verifyEmail($account))]);
    }

    public function suspend(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);

        return response()->json(['data' => $this->resource(app(AccountsManager::class)->suspend($account, (string) $request->user()->getAuthIdentifier()))]);
    }

    public function reactivate(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);

        return response()->json(['data' => $this->resource(app(AccountsManager::class)->reactivate($account, (string) $request->user()->getAuthIdentifier()))]);
    }

    public function ban(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'ends_at' => ['nullable', 'date', 'after:now'],
        ]);
        $ban = app(AccountsManager::class)->ban($account, $data['reason'], $data['ends_at'] ?? null, (string) $request->user()->getAuthIdentifier());

        return response()->json(['data' => $this->banResource($ban)], 201);
    }

    public function liftBan(Request $request, AccountsRecord $account, AccountBan $ban): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $lifted = app(AccountsManager::class)->liftBan($account, $ban, (string) $request->user()->getAuthIdentifier());

        return response()->json(['data' => $this->banResource($lifted)]);
    }

    public function privacy(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $data = $request->validate([
            'profile_visibility' => ['required', 'in:private,friends,public'],
            'marketing_consent' => ['required', 'boolean'],
            'analytics_consent' => ['required', 'boolean'],
        ]);
        $privacy = app(AccountsManager::class)->updatePrivacy($account, $data['profile_visibility'], (bool) $data['marketing_consent'], (bool) $data['analytics_consent']);

        return response()->json(['data' => $this->privacyResource($privacy)]);
    }

    public function requestDeletion(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $privacy = app(AccountsManager::class)->requestDeletion($account);

        return response()->json(['data' => $this->privacyResource($privacy)]);
    }

    public function completeDeletion(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $privacy = app(AccountsManager::class)->completeDeletion($account, (string) $request->user()->getAuthIdentifier());

        return response()->json(['data' => $this->privacyResource($privacy)]);
    }

    public function revokeSession(Request $request, AccountsRecord $account, AccountSession $session): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $revoked = app(AccountsManager::class)->revokeSession($account, $session, (string) $request->user()->getAuthIdentifier());

        return response()->json(['data' => ['id' => (string) $revoked->getKey(), 'type' => 'browser-game-account-session', 'attributes' => $revoked->only(['last_seen_at', 'expires_at', 'revoked_at'])]]);
    }

    public function sessions(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);

        return response()->json(['data' => $account->sessions()->latest()->get()->map(fn (AccountSession $session): array => [
            'id' => (string) $session->getKey(),
            'type' => 'browser-game-account-session',
            'attributes' => $session->only(['ip_address', 'user_agent', 'last_seen_at', 'expires_at', 'revoked_at']),
        ])->values()]);
    }

    public function revokeAllSessions(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $count = app(AccountsManager::class)->revokeAllSessions($account, (string) $request->user()->getAuthIdentifier());

        return response()->json(['data' => ['revoked' => $count]]);
    }

    public function issueRecovery(Request $request, AccountsRecord $account): JsonResponse
    {
        $account = $this->visibleAccount($request, $account);
        $recovery = app(AccountsManager::class)->issueRecovery($account, (int) config('browser-game.accounts.recovery_minutes', 30));

        return response()->json(['data' => [
            'id' => (string) $recovery['recovery']->getKey(),
            'type' => 'browser-game-account-recovery',
            'attributes' => ['expires_at' => $recovery['recovery']->expires_at],
        ]], 202);
    }

    public function consumeRecovery(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'min:40']]);
        $account = app(AccountsManager::class)->consumeRecovery($data['token']);
        abort_if($account === null, 422, 'The recovery token is invalid or expired.');

        return response()->json(['data' => ['id' => (string) $account->getKey(), 'type' => 'browser-game-account-recovery', 'attributes' => ['consumed' => true]]]);
    }

    private function resource(Model $account): array
    {
        return ['id' => (string) $account->getKey(), 'type' => 'browser-game-account', 'attributes' => [
            'name' => $account->getAttribute('name'),
            'email' => $account->getAttribute('email'),
            'username' => $account->getAttribute('username'),
            'status' => $account->getAttribute('status'),
            'region' => $account->getAttribute('region'),
            'age_verified' => $account->getAttribute('age_verified'),
            'data' => $account->getAttribute('data'),
            'tenant_id' => $account->getAttribute('tenant_id'),
            'team_id' => $account->getAttribute('team_id'),
        ]];
    }

    private function visibleAccount(Request $request, AccountsRecord $account): AccountsRecord
    {
        $team = $request->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        return app(AccountsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())
            ->whereKey($account->getKey())->firstOrFail();
    }

    private function privacyResource(AccountPrivacy $privacy): array
    {
        return ['id' => (string) $privacy->getKey(), 'type' => 'browser-game-account-privacy', 'attributes' => [
            'profile_visibility' => $privacy->profile_visibility,
            'marketing_consent' => (bool) $privacy->marketing_consent,
            'analytics_consent' => (bool) $privacy->analytics_consent,
            'deletion_requested_at' => $privacy->deletion_requested_at?->toISOString(),
            'deletion_completed_at' => $privacy->deletion_completed_at?->toISOString(),
        ]];
    }

    private function banResource(AccountBan $ban): array
    {
        return ['id' => (string) $ban->getKey(), 'type' => 'browser-game-account-ban', 'attributes' => $ban->only(['account_id', 'reason', 'scope', 'starts_at', 'ends_at', 'revoked_at', 'issued_by'])];
    }
}
