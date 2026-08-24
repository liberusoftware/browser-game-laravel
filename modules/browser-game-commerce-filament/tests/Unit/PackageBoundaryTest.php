<?php

use Liberu\BrowserGame\CommerceFilament\CommerceFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CommerceFilamentServiceProvider::class))->toBeTrue();
});
