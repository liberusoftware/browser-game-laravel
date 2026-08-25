<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsApi;

use Illuminate\Support\ServiceProvider;

final class QuestsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
