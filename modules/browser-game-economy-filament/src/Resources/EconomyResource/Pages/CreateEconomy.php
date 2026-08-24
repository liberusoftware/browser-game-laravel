<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\EconomyFilament\Resources\EconomyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\EconomyFilament\Resources\EconomyResource;

final class CreateEconomy extends CreateRecord
{
    protected static string $resource = EconomyResource::class;
}
