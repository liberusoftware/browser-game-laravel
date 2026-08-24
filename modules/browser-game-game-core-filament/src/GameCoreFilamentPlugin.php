<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource;

final class GameCoreFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-game-core';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([GameWorldResource::class]);
    }

    public function boot(Panel $panel): void {}
}
