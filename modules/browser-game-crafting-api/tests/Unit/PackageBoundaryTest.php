<?php

use Liberu\BrowserGame\CraftingApi\CraftingApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CraftingApiServiceProvider::class))->toBeTrue();
});
