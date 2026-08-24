<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\ModerationAndAnalyticsLivewire\Livewire\ModerationAndAnalyticsCatalog;
use Livewire\Livewire;

final class ModerationAndAnalyticsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.moderation-and-analytics.catalog', ModerationAndAnalyticsCatalog::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-moderation-and-analytics-livewire');
    }
}
