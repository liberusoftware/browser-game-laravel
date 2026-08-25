<?php

use Liberu\BrowserGame\EconomyLivewire\EconomyLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(EconomyLivewireServiceProvider::class))->toBeTrue();
});
