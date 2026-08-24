<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts;

use Illuminate\Support\ServiceProvider;

final class AccountsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
