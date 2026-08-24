<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource;

final class ListCrafting extends ListRecords
{
    protected static string $resource = CraftingResource::class;
}
