<?php

namespace App\Filament\Admin\Resources\GameResourceResource\Pages;

use App\Filament\Admin\Resources\GameResourceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGameResource extends ViewRecord
{
    protected static string $resource = GameResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
