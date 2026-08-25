<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource;

final class CraftingFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-crafting';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CraftingResource::class]);
    }

    public function boot(Panel $panel): void {}
}
