<?php

use Liberu\BrowserGame\WorldFilament\WorldFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(WorldFilamentServiceProvider::class))->toBeTrue();
});
