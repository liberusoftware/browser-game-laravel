<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource;

final class CreateCrafting extends CreateRecord
{
    protected static string $resource = CraftingResource::class;
}
