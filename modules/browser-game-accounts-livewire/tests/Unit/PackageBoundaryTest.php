<?php

use Liberu\BrowserGame\AccountsLivewire\AccountsLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(AccountsLivewireServiceProvider::class))->toBeTrue();
});
