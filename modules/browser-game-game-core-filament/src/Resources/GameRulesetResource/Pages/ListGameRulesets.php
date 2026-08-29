<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameRulesetResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameRulesetResource;

final class ListGameRulesets extends ListRecords
{
    protected static string $resource = GameRulesetResource::class;
}
