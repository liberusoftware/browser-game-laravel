<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\QuestsFilament\Resources\QuestResource;

final class QuestsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-quests';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([QuestResource::class]);
    }

    public function boot(Panel $panel): void {}
}
