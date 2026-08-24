<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource;

final class LiveOpsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-live-ops';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([LiveOpsResource::class]);
    }

    public function boot(Panel $panel): void {}
}
