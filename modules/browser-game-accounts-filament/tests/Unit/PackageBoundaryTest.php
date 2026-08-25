<?php

use Liberu\BrowserGame\AccountsFilament\AccountsFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(AccountsFilamentServiceProvider::class))->toBeTrue();
});
