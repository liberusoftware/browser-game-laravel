<?php

use Liberu\BrowserGame\CombatApi\CombatApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CombatApiServiceProvider::class))->toBeTrue();
});
