<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\GameCoreFilament\Resources\GameFeatureFlagResource;

final class ListGameFeatureFlags extends ListRecords
{
    protected static string $resource = GameFeatureFlagResource::class;
}
