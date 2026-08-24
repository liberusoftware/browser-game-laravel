<?php

use Liberu\BrowserGame\ItemsFilament\ItemsFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(ItemsFilamentServiceProvider::class))->toBeTrue();
});
