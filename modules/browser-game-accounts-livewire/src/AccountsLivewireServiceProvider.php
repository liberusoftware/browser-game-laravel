<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\AccountsLivewire\Livewire\AccountsCatalog;
use Livewire\Livewire;

final class AccountsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.accounts.catalog', AccountsCatalog::class);
        Livewire::addNamespace('module-browser-game-accounts', classNamespace: 'Liberu\\BrowserGame\\AccountsLivewire\\Livewire');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-accounts-livewire');
    }
}
