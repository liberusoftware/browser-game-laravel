<?php

use Liberu\BrowserGame\WorldLivewire\WorldLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(WorldLivewireServiceProvider::class))->toBeTrue();
});
