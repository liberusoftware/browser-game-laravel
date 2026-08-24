<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatApi;

use Illuminate\Support\ServiceProvider;

final class CombatApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
