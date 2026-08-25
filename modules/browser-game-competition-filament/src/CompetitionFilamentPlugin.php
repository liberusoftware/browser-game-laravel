<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\CompetitionFilament\Resources\CompetitionResource;

final class CompetitionFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-competition';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CompetitionResource::class]);
    }

    public function boot(Panel $panel): void {}
}
