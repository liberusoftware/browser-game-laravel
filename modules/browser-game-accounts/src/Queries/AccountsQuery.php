<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Accounts\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Accounts\Models\AccountsRecord;

final class AccountsQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return AccountsRecord::query()
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
