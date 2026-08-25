<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\CombatFilament\Resources\CombatDefinitionResource;

final class EditCombatDefinition extends EditRecord
{
    protected static string $resource = CombatDefinitionResource::class;
}
