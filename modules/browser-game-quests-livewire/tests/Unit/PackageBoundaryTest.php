<?php

use Liberu\BrowserGame\QuestsLivewire\QuestsLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(QuestsLivewireServiceProvider::class))->toBeTrue();
});
