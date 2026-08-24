<?php

use Liberu\BrowserGame\LiveOpsApi\LiveOpsApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(LiveOpsApiServiceProvider::class))->toBeTrue();
});
