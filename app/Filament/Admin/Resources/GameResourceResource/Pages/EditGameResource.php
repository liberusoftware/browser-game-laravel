<?php

namespace App\Filament\Admin\Resources\GameResourceResource\Pages;

use App\Filament\Admin\Resources\GameResourceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameResource extends EditRecord
{
    protected static string $resource = GameResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
