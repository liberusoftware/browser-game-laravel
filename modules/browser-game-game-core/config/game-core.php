<?php

declare(strict_types=1);

return [
    'table_prefix' => 'browser_game_',
    'idempotency_ttl' => 86400,
    'maintenance_statuses' => ['scheduled', 'active', 'resolved'],
];
