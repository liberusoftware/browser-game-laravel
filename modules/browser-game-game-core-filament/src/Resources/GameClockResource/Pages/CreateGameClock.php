<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource;

final class CreateGameClock extends CreateRecord
{
    protected static string $resource = GameClockResource::class;
}
