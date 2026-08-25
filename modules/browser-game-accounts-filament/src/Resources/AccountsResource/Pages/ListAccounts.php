<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource;

final class ListAccounts extends ListRecords
{
    protected static string $resource = AccountsResource::class;
}
