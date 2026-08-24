<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Crafting\Events\CraftingDefined;
use Liberu\BrowserGame\Crafting\Models\CraftingRecord;

final class CraftingManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CraftingRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): CraftingRecord => CraftingRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        CraftingDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
