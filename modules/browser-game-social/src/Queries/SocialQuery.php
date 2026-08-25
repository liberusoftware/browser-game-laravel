<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Queries;

use Illuminate\Database\Eloquent\Builder;
use Liberu\BrowserGame\Social\Models\SocialRecord;

final class SocialQuery
{
    public function visible(?string $tenantId, ?string $teamId): Builder
    {
        return SocialRecord::query()
            ->where(fn (Builder $q): Builder => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->where(fn (Builder $q): Builder => $q->whereNull('team_id')->orWhere('team_id', $teamId));
    }
}
