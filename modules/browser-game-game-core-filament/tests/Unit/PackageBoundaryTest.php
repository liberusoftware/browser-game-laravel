<?php

use Liberu\BrowserGame\GameCoreFilament\GameCoreFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(GameCoreFilamentServiceProvider::class))->toBeTrue();
});
