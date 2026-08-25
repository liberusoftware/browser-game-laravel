<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource;

final class ListLiveOps extends ListRecords
{
    protected static string $resource = LiveOpsResource::class;
}
