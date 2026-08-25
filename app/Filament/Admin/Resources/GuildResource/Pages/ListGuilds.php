<?php

namespace App\Filament\Admin\Resources\GuildResource\Pages;

use App\Filament\Admin\Resources\GuildResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGuilds extends ListRecords
{
    protected static string $resource = GuildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
