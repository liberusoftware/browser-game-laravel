<?php

use Liberu\BrowserGame\AccountsApi\AccountsApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(AccountsApiServiceProvider::class))->toBeTrue();
});
