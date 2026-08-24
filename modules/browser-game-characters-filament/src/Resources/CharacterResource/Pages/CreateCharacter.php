<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Liberu\BrowserGame\Characters\Support\CharactersManager;
use Liberu\BrowserGame\CharactersFilament\Resources\CharacterResource;

final class CreateCharacter extends CreateRecord
{
    protected static string $resource = CharacterResource::class;

    protected function handleRecordCreation(array $data): GameCharacter
    {
        return app(CharactersManager::class)->create(
            (string) $data['player_id'],
            (string) $data['name'],
            (string) $data['race'],
            (string) $data['class'],
            $data['background'] ?? null,
            $data['statistics'] ?? [],
            $data['skills'] ?? [],
            $data['world_id'] ?? null,
            $data['team_id'] ?? null,
        );
    }
}
