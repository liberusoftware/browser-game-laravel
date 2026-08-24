<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsApi;

use Illuminate\Support\ServiceProvider;

final class ItemsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
