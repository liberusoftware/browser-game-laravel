<?php

return [
    // Composer-installed module packages. Local paths may be appended for development.
    'paths' => [base_path('modules')],

    // Module manifests decide their default state. These environment overrides are the
    // deployment control plane and keep the host independent from module internals.
    'enabled' => array_values(array_filter(explode(',', (string) env('MODULES_ENABLED', '')))),
    'disabled' => array_values(array_filter(explode(',', (string) env('MODULES_DISABLED', '')))),

    'cache' => env('MODULES_CACHE', false),
    'cache_key' => 'liberu.modules.registry.v1',
];
