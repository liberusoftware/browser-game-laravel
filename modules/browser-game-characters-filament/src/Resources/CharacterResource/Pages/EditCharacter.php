<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource;

final class EditCharacter extends EditRecord
{
    protected static string $resource = CharacterResource::class;
}
