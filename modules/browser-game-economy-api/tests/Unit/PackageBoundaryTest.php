<?php

use Liberu\BrowserGame\EconomyApi\EconomyApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(EconomyApiServiceProvider::class))->toBeTrue();
});
