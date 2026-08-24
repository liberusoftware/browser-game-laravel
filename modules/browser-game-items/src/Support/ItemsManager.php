<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Items\Events\ItemsDefined;
use Liberu\BrowserGame\Items\Models\ItemsRecord;

final class ItemsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): ItemsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): ItemsRecord => ItemsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        ItemsDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
