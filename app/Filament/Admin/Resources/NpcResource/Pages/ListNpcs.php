<?php

namespace App\Filament\Admin\Resources\NpcResource\Pages;

use App\Filament\Admin\Resources\NpcResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNpcs extends ListRecords
{
    protected static string $resource = NpcResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
