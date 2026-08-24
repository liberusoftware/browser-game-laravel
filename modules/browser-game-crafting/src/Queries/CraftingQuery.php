<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Crafting\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Crafting\Models\CraftingRecord;

final class CraftingQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return CraftingRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
