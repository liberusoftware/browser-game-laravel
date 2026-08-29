<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource;

final class CreateGameMaintenanceState extends CreateRecord
{
    protected static string $resource = GameMaintenanceStateResource::class;
}
