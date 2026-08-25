<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsApi;

use Illuminate\Support\ServiceProvider;

final class LiveOpsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
