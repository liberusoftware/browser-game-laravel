<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource;

final class ListCommerce extends ListRecords
{
    protected static string $resource = CommerceResource::class;
}
