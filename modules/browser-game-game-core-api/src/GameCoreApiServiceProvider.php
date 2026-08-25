<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreApi;

use Illuminate\Support\ServiceProvider;

final class GameCoreApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
