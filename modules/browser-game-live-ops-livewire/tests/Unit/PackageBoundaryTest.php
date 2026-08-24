<?php

use Liberu\BrowserGame\LiveOpsLivewire\LiveOpsLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(LiveOpsLivewireServiceProvider::class))->toBeTrue();
});
