<?php

namespace App\Filament\Admin\Resources\PlayerItemResource\Pages;

use App\Filament\Admin\Resources\PlayerItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlayerItem extends EditRecord
{
    protected static string $resource = PlayerItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
