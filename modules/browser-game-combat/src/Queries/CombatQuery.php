<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Combat\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Combat\Models\CombatBattle;

final class CombatQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return CombatBattle::query()->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
