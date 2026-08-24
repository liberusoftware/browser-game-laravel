<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Competition\Events\CompetitionDefined;
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;

final class CompetitionManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CompetitionRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): CompetitionRecord => CompetitionRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        CompetitionDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
