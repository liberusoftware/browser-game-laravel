<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\ModerationAndAnalytics\Models\ModerationAndAnalyticsRecord;

final class ModerationAndAnalyticsQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return ModerationAndAnalyticsRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
