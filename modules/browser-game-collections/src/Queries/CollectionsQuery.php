<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Collections\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Collections\Models\CollectionsRecord;

final class CollectionsQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return CollectionsRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
