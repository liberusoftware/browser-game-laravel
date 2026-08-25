<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsFilament\Resources\QuestResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\QuestsFilament\Resources\QuestResource;

final class EditQuest extends EditRecord
{
    protected static string $resource = QuestResource::class;
}
