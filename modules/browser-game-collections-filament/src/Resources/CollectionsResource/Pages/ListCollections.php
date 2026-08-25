<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource;

final class ListCollections extends ListRecords
{
    protected static string $resource = CollectionsResource::class;
}
