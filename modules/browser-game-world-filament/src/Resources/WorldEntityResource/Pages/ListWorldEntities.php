<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource;

final class ListWorldEntities extends ListRecords
{
    protected static string $resource = WorldEntityResource::class;
}
