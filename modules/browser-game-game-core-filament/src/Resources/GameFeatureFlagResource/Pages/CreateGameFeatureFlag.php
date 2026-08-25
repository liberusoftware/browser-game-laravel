<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource;

final class CreateGameFeatureFlag extends CreateRecord
{
    protected static string $resource = GameFeatureFlagResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return array_merge($data, ['changed_by' => auth()->id()]);
    }
}
