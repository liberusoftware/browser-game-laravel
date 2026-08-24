<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\SocialFilament\Resources\SocialResource;

final class SocialFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-social';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([SocialResource::class]);
    }

    public function boot(Panel $panel): void {}
}
