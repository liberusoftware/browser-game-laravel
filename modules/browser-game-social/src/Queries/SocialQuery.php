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
            ->when($tenantId, fn (Builder $q, string $v): Builder => $q->where('tenant_id', $v))
            ->when($teamId, fn (Builder $q, string $v): Builder => $q->where('team_id', $v));
    }
}
