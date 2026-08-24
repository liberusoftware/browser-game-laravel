<?php

declare(strict_types=1);

return [
    'minimum_age' => (int) env('BROWSER_GAME_ACCOUNTS_MINIMUM_AGE', 13),
    'recovery_minutes' => (int) env('BROWSER_GAME_ACCOUNTS_RECOVERY_MINUTES', 30),
    'maximum_recovery_attempts' => (int) env('BROWSER_GAME_ACCOUNTS_MAX_RECOVERY_ATTEMPTS', 5),
];
