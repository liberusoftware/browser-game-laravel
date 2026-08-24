<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Commerce\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Commerce\Models\CommerceRecord;

final class CommerceQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return CommerceRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
