<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Economy\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Economy\Models\EconomyRecord;

final class EconomyQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return EconomyRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
