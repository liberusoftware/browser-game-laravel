<?php

use Liberu\BrowserGame\CommerceLivewire\CommerceLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CommerceLivewireServiceProvider::class))->toBeTrue();
});
