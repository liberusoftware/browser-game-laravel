<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Quests\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Quests\Models\Quest;

final class QuestQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return Quest::query()->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
