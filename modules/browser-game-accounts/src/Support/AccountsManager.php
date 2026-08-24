<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Accounts\Events\AccountsDefined;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;

final class AccountsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): AccountsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): AccountsRecord => AccountsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        AccountsDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
