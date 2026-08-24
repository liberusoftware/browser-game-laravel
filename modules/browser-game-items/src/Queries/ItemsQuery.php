<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Items\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Items\Models\ItemsRecord;

final class ItemsQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return ItemsRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
