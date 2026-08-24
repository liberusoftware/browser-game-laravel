<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ItemsFilament\Resources\ItemsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\ItemsFilament\Resources\ItemsResource;

final class ListItems extends ListRecords
{
    protected static string $resource = ItemsResource::class;
}
