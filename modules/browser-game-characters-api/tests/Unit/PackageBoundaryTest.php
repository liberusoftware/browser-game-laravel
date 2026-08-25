<?php

use Liberu\BrowserGame\CharactersApi\CharactersApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CharactersApiServiceProvider::class))->toBeTrue();
});
