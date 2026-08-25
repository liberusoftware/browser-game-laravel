<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\ModerationAndAnalyticsFilament\Resources\ModerationAndAnalyticsResource;

final class ModerationAndAnalyticsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-moderation-and-analytics';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ModerationAndAnalyticsResource::class]);
    }

    public function boot(Panel $panel): void {}
}
