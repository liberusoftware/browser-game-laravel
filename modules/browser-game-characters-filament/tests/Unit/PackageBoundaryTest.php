<?php

use Liberu\BrowserGame\CharactersFilament\CharactersFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CharactersFilamentServiceProvider::class))->toBeTrue();
});
