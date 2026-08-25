<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource;

final class CreateCombatDefinition extends CreateRecord
{
    protected static string $resource = CombatDefinitionResource::class;
}
