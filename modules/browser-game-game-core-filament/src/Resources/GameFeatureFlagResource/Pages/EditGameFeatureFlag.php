<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource;

final class EditGameFeatureFlag extends EditRecord
{
    protected static string $resource = GameFeatureFlagResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return array_merge($data, ['changed_by' => auth()->id()]);
    }
}
