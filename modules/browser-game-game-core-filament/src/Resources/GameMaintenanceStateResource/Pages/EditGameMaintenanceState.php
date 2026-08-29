<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameMaintenanceStateResource;

final class EditGameMaintenanceState extends EditRecord
{
    protected static string $resource = GameMaintenanceStateResource::class;
}
