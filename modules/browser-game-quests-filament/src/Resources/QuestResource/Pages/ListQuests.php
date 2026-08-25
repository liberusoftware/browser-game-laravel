<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsFilament\Resources\QuestResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\QuestsFilament\Resources\QuestResource;

final class ListQuests extends ListRecords
{
    protected static string $resource = QuestResource::class;
}
