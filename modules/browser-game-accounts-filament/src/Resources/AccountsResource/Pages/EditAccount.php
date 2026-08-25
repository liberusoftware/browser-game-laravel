<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\BrowserGame\AccountsFilament\Resources\AccountsResource;

final class EditAccount extends EditRecord
{
    protected static string $resource = AccountsResource::class;
}
