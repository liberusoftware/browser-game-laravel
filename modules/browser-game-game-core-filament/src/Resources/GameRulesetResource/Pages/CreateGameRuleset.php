<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameRulesetResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameRulesetResource;

final class CreateGameRuleset extends CreateRecord
{
    protected static string $resource = GameRulesetResource::class;
}
