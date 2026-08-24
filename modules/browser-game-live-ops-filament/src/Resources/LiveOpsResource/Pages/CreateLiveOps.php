<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource;

final class CreateLiveOps extends CreateRecord
{
    protected static string $resource = LiveOpsResource::class;
}
