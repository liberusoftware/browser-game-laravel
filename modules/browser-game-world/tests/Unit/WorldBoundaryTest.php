<?php

declare(strict_types=1);

use Liberu\BrowserGame\World\Models\WorldEntity;
use Liberu\BrowserGame\World\Policies\WorldPolicy;

it('keeps world visibility within the tenant and team scope', function (): void {
    $entity = new WorldEntity(['tenant_id' => 'tenant-1', 'team_id' => 'team-1']);
    $policy = app(WorldPolicy::class);

    expect($policy->view('tenant-1', 'team-1', $entity))->toBeTrue()
        ->and($policy->view('tenant-2', 'team-1', $entity))->toBeFalse();
});
