<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource;

final class ListGameClocks extends ListRecords
{
    protected static string $resource = GameClockResource::class;
}
