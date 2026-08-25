<?php

use Liberu\BrowserGame\WorldApi\WorldApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(WorldApiServiceProvider::class))->toBeTrue();
});
