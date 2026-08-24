<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\World\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\World\Models\WorldEntity;

final class WorldQuery
{
    public function visible(?string $tenantId, ?string $teamId, ?string $kind = null): Builder
    {
        return WorldEntity::query()->where(fn (Builder $query): Builder => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))->where(fn (Builder $query): Builder => $query->whereNull('team_id')->orWhere('team_id', $teamId))->when($kind, fn (Builder $query): Builder => $query->where('kind', $kind));
    }
}
