<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameContentVersionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameContentVersionResource;

final class CreateGameContentVersion extends CreateRecord
{
    protected static string $resource = GameContentVersionResource::class;
}
