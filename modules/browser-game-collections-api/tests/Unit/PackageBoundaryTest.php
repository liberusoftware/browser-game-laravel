<?php

use Liberu\BrowserGame\CollectionsApi\CollectionsApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CollectionsApiServiceProvider::class))->toBeTrue();
});
