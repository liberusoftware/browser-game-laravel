<?php

use Liberu\BrowserGame\CollectionsLivewire\CollectionsLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CollectionsLivewireServiceProvider::class))->toBeTrue();
});
