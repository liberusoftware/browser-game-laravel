<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource;

final class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountsResource::class;
}
