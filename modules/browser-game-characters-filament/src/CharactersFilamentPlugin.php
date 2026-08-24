<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource;

final class CharactersFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-characters';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CharacterResource::class]);
    }

    public function boot(Panel $panel): void {}
}
