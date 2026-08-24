<?php

use Liberu\BrowserGame\QuestsFilament\QuestsFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(QuestsFilamentServiceProvider::class))->toBeTrue();
});
