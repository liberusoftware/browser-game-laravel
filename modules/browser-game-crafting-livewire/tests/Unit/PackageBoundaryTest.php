<?php

use Liberu\BrowserGame\CraftingLivewire\CraftingLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CraftingLivewireServiceProvider::class))->toBeTrue();
});
