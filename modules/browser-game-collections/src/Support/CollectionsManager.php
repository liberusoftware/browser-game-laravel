<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Collections\Events\CollectionsDefined;
use Liberu\BrowserGame\Collections\Models\CollectionsRecord;

final class CollectionsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CollectionsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): CollectionsRecord => CollectionsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        CollectionsDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
