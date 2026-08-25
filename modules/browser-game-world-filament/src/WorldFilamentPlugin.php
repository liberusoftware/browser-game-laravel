<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource;

final class WorldFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-world';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([WorldEntityResource::class]);
    }

    public function boot(Panel $panel): void {}
}
