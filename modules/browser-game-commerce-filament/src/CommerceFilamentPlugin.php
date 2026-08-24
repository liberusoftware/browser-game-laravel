<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource;

final class CommerceFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-commerce';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CommerceResource::class]);
    }

    public function boot(Panel $panel): void {}
}
