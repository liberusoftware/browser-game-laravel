<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\CombatFilament\Resources\CombatBattleResource;

final class EditCombatBattle extends EditRecord
{
    protected static string $resource = CombatBattleResource::class;
}
