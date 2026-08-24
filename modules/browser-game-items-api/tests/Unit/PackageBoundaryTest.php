<?php

use Liberu\BrowserGame\ItemsApi\ItemsApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(ItemsApiServiceProvider::class))->toBeTrue();
});
