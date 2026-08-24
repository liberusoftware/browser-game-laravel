<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingApi;

use Illuminate\Support\ServiceProvider;

final class CraftingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
