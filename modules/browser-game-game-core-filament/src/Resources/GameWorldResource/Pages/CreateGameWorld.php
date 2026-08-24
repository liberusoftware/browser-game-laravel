<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameWorldResource;

final class CreateGameWorld extends CreateRecord
{
    protected static string $resource = GameWorldResource::class;
}
