<?php

use Liberu\BrowserGame\ItemsLivewire\ItemsLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(ItemsLivewireServiceProvider::class))->toBeTrue();
});
