<?php

use Liberu\BrowserGame\GameCoreLivewire\GameCoreLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(GameCoreLivewireServiceProvider::class))->toBeTrue();
});
