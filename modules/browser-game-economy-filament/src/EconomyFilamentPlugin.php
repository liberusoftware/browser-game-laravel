<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\EconomyFilament\Resources\EconomyResource;

final class EconomyFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-economy';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([EconomyResource::class]);
    }

    public function boot(Panel $panel): void {}
}
