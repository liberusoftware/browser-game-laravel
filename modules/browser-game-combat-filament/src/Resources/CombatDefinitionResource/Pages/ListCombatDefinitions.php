<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource;

final class ListCombatDefinitions extends ListRecords
{
    protected static string $resource = CombatDefinitionResource::class;
}
