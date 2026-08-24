<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource;

final class CreateCombatBattle extends CreateRecord
{
    protected static string $resource = CombatBattleResource::class;
}
