<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource;

final class ListCombatBattles extends ListRecords
{
    protected static string $resource = CombatBattleResource::class;
}
