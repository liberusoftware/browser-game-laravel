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
            ->where(fn (Builder $q): Builder => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where(fn (Builder $q): Builder => $q->whereNull('team_id')->orWhere('team_id', $teamId));
    }
}
