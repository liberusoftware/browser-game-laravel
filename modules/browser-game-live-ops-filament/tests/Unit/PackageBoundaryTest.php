<?php

use Liberu\BrowserGame\LiveOpsFilament\LiveOpsFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(LiveOpsFilamentServiceProvider::class))->toBeTrue();
});
