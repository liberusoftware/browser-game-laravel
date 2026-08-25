<?php

use Liberu\BrowserGame\CombatLivewire\CombatLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CombatLivewireServiceProvider::class))->toBeTrue();
});
