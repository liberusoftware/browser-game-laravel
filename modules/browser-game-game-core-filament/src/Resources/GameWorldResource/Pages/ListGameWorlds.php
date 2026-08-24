<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource;

final class ListGameWorlds extends ListRecords
{
    protected static string $resource = GameWorldResource::class;
}
