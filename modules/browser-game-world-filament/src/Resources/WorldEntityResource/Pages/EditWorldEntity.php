<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\WorldFilament\Resources\WorldEntityResource;

final class EditWorldEntity extends EditRecord
{
    protected static string $resource = WorldEntityResource::class;
}
