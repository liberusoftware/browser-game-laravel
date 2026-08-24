<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\LiveOpsFilament\Resources\LiveOpsResource;

final class EditLiveOps extends EditRecord
{
    protected static string $resource = LiveOpsResource::class;
}
