<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\CollectionsFilament\Resources\CollectionsResource;

final class EditCollections extends EditRecord
{
    protected static string $resource = CollectionsResource::class;
}
