<?php

use Liberu\BrowserGame\EconomyFilament\EconomyFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(EconomyFilamentServiceProvider::class))->toBeTrue();
});
