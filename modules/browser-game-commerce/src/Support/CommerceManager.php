<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Commerce\Events\CommerceDefined;
use Liberu\BrowserGame\Commerce\Models\CommerceRecord;

final class CommerceManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CommerceRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): CommerceRecord => CommerceRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        CommerceDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
