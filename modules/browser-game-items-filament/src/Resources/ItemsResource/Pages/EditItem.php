<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsFilament\Resources\ItemsResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\ItemsFilament\Resources\ItemsResource;

final class EditItem extends EditRecord
{
    protected static string $resource = ItemsResource::class;
}
