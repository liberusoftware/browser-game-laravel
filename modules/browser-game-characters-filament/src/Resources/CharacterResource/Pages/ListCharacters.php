<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource;

final class ListCharacters extends ListRecords
{
    protected static string $resource = CharacterResource::class;
}
