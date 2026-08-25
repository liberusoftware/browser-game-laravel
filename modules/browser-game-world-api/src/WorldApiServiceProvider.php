<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldApi;

use Illuminate\Support\ServiceProvider;

final class WorldApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
