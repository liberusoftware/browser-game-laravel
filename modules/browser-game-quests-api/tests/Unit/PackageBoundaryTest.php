<?php

use Liberu\BrowserGame\QuestsApi\QuestsApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(QuestsApiServiceProvider::class))->toBeTrue();
});
