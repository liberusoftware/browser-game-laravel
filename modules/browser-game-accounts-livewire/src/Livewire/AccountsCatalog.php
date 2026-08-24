<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsLivewire\Livewire;

use Liberu\BrowserGame\Accounts\Queries\AccountsQuery;
use Livewire\Component;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;

final class AccountsCatalog extends Component
{
    public string $name = '';

    public ?string $email = null;

    public ?string $username = null;

    public ?string $profileVisibility = null;

    public bool $marketingConsent = false;

    public bool $analyticsConsent = false;

    public function updateIdentity(string $accountId): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email'], 'username' => ['nullable', 'regex:/^[A-Za-z0-9_.-]{3,50}$/']]);
        $account = $this->visibleAccount($accountId)->where('status', 'active')->firstOrFail();
        app(AccountsManager::class)->updateIdentity($account, $this->name, $this->email, $this->username);
        $this->dispatch('account-updated');
    }

    public function updatePrivacy(string $accountId): void
    {
        $this->validate(['profileVisibility' => ['required', 'in:private,friends,public']]);
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->updatePrivacy($account, (string) $this->profileVisibility, $this->marketingConsent, $this->analyticsConsent);
        $this->dispatch('privacy-updated');
    }

    public function requestDeletion(string $accountId): void
    {
        $account = $this->visibleAccount($accountId)->firstOrFail();
        app(AccountsManager::class)->requestDeletion($account);
        $this->dispatch('account-deletion-requested');
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $accounts = app(AccountsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-accounts-livewire::accounts-catalog', ['accounts' => $accounts]);
    }

    private function visibleAccount(string $accountId): \Illuminate\Database\Eloquent\Builder
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_unless($teamId !== null, 404);

        return app(AccountsQuery::class)->visible(null, (string) $teamId)->whereKey($accountId);
    }
}
