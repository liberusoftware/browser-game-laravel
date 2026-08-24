<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;

final class CompetitionQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return CompetitionRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
