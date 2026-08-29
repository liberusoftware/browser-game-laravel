<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameContentVersionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameContentVersionResource;

final class ListGameContentVersions extends ListRecords
{
    protected static string $resource = GameContentVersionResource::class;
}
