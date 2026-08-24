<?php

use Liberu\BrowserGame\CraftingFilament\CraftingFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CraftingFilamentServiceProvider::class))->toBeTrue();
});
