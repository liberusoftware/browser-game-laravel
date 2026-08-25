<?php

namespace App\Filament\Admin\Resources\QuestResource\Pages;

use App\Filament\Admin\Resources\QuestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuest extends ViewRecord
{
    protected static string $resource = QuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
