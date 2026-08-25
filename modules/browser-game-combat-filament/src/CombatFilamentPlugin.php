<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource;

final class CombatFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-combat';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CombatBattleResource::class, CombatDefinitionResource::class]);
    }

    public function boot(Panel $panel): void {}
}
