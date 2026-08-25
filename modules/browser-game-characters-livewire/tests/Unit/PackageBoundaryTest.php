<?php

use Liberu\BrowserGame\CharactersLivewire\CharactersLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CharactersLivewireServiceProvider::class))->toBeTrue();
});
