<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\ModerationAndAnalytics\Events\ModerationAndAnalyticsDefined;
use Liberu\BrowserGame\ModerationAndAnalytics\Models\ModerationAndAnalyticsRecord;

final class ModerationAndAnalyticsManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): ModerationAndAnalyticsRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): ModerationAndAnalyticsRecord => ModerationAndAnalyticsRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        ModerationAndAnalyticsDefined::dispatch((string) $record->getKey());

        return $record;
    }
}
