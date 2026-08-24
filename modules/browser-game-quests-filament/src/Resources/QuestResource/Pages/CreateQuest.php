<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsFilament\Resources\QuestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\QuestsFilament\Resources\QuestResource;

final class CreateQuest extends CreateRecord
{
    protected static string $resource = QuestResource::class;
}
