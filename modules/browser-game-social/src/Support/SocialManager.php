<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Social\Events\SocialDefined;
use Liberu\BrowserGame\Social\Models\SocialRecord;

final class SocialManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): SocialRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): SocialRecord => SocialRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        SocialDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
