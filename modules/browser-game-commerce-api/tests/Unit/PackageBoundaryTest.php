<?php

use Liberu\BrowserGame\CommerceApi\CommerceApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CommerceApiServiceProvider::class))->toBeTrue();
});
