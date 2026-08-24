<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\CraftingFilament\Resources\CraftingResource;

final class EditCrafting extends EditRecord
{
    protected static string $resource = CraftingResource::class;
}
