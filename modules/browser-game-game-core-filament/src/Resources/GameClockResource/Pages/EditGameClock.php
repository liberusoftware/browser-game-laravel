<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameClockResource;

final class EditGameClock extends EditRecord
{
    protected static string $resource = GameClockResource::class;
}
