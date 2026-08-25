<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\ItemsFilament\Resources\ItemsResource;

final class ItemsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-items';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ItemsResource::class]);
    }

    public function boot(Panel $panel): void {}
}
