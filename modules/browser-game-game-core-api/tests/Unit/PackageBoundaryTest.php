<?php

use Liberu\BrowserGame\GameCoreApi\GameCoreApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(GameCoreApiServiceProvider::class))->toBeTrue();
});
