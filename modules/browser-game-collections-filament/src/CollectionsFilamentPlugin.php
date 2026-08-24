<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource;

final class CollectionsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-collections';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CollectionsResource::class]);
    }

    public function boot(Panel $panel): void {}
}
