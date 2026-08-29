<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource;

final class ListGameMaintenanceStates extends ListRecords
{
    protected static string $resource = GameMaintenanceStateResource::class;
}
