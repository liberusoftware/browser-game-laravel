<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsLivewire\Livewire;

use Liberu\BrowserGame\Accounts\Queries\AccountsQuery;
use Livewire\Component;

final class AccountsCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $accounts = app(AccountsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-accounts-livewire::accounts-catalog', ['accounts' => $accounts]);
    }
}
