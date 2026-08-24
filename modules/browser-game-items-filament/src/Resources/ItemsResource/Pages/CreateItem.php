<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsFilament\Resources\ItemsResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\ItemsFilament\Resources\ItemsResource;

final class CreateItem extends CreateRecord
{
    protected static string $resource = ItemsResource::class;
}
