<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Liberu\BrowserGame\CombatFilament\CombatFilamentPlugin;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource;

it('registers combat definition administration in the Filament plugin', function (): void {
    expect(CombatFilamentPlugin::make()->getId())->toBe('browser-game-combat')
        ->and(CombatDefinitionResource::form(Schema::make())->getComponents())->not->toBeEmpty()
        ->and(CombatDefinitionResource::getPages())->not->toBeEmpty();
});
