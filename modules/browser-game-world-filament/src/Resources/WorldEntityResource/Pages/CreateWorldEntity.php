<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource;

final class CreateWorldEntity extends CreateRecord
{
    protected static string $resource = WorldEntityResource::class;
}
