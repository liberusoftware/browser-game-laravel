<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialLivewire;

use Illuminate\Support\ServiceProvider;
use Liberu\BrowserGame\SocialLivewire\Livewire\SocialCatalog;
use Livewire\Livewire;

final class SocialLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('browser-game.social.catalog', SocialCatalog::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'browser-game-social-livewire');
    }
}
