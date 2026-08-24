<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\CommerceFilament\Resources\CommerceResource;

final class CreateCommerce extends CreateRecord
{
    protected static string $resource = CommerceResource::class;
}
