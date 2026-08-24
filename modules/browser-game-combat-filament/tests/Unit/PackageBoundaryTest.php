<?php

use Liberu\BrowserGame\CombatFilament\CombatFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CombatFilamentServiceProvider::class))->toBeTrue();
});
