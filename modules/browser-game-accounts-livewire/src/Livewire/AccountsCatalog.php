<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsLivewire\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Accounts\Queries\AccountsQuery;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;
use Livewire\Component;

final class AccountsCatalog extends Component
{
    public string $name = '';

    public ?string $email = null;

    public ?string $username = null;

    public ?string $profileVisibility = null;

    public bool $marketingConsent = false;

    public bool $analyticsConsent = false;

    public ?int $birthYear = null;

    public ?string $region = null;

    public bool $ageVerified = false;

    public ?string $message = null;

    public function updateIdentity(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email'], 'username' => ['nullable', 'regex:/^[A-Za-z0-9_.-]{3,50}$/']]);
        $account = $this->visibleAccount($accountId)->where('status', 'active')->firstOrFail();
        app(AccountsManager::class)->updateIdentity($account, $this->name, $this->email, $this->username);
        $this->dispatch('account-updated');
    }

    public function updatePrivacy(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $this->validate(['profileVisibility' => ['required', 'in:private,friends,public']]);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->updatePrivacy($account, (string) $this->profileVisibility, $this->marketingConsent, $this->analyticsConsent);
        $this->dispatch('privacy-updated');
    }

    public function verifyEmail(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->verifyEmail($account);
        $this->message = 'Email verified.';
    }

    public function updateAgeRegion(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $this->validate(['birthYear' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year], 'region' => ['nullable', 'string', 'size:2']]);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->setAgeRegionPolicy($account, $this->birthYear, $this->region, $this->ageVerified);
        $this->message = 'Age and region policy updated.';
    }

    public function revokeAllSessions(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->revokeAllSessions($account, (string) auth()->id());
        $this->message = 'All sessions revoked.';
    }

    public function issueRecovery(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->issueRecovery($account);
        $this->message = 'Recovery issued.';
    }

    public function requestDeletion(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->requestDeletion($account);
        $this->message = 'Account deletion requested.';
        $this->dispatch('account-deletion-requested');
    }

    public function completeDeletion(string $accountId): void
    {
        abort_unless(auth()->check(), 403);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->completeDeletion($account, (string) auth()->id());
        $this->message = 'Account deletion completed.';
        $this->dispatch('account-deleted');
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $accounts = app(AccountsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->with('privacy')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-accounts-livewire::accounts-catalog', ['accounts' => $accounts]);
    }

    private function visibleAccount(string $accountId): Builder
    {
        $team = auth()->user()?->currentTeam;
        abort_unless($team?->getKey() !== null, 404);

        return app(AccountsQuery::class)->visible($team->getAttribute('tenant_id'), (string) $team->getKey())->whereKey($accountId);
    }
}
