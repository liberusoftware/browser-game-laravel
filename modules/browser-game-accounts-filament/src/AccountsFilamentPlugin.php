<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource;

final class AccountsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'browser-game-accounts';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AccountsResource::class]);
    }

    public function boot(Panel $panel): void {}
}
