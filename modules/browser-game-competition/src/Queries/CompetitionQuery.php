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
            ->where(fn (Builder $q): Builder => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where(fn (Builder $q): Builder => $q->whereNull('team_id')->orWhere('team_id', $teamId));
    }
}
