<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource;

final class CreateCollections extends CreateRecord
{
    protected static string $resource = CollectionsResource::class;
}
