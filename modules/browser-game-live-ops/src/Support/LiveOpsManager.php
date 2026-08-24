<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOps\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\LiveOps\Events\LiveOpsDefined;
use Liberu\BrowserGame\LiveOps\Models\LiveOpsRecord;

final class LiveOpsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): LiveOpsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): LiveOpsRecord => LiveOpsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        LiveOpsDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
