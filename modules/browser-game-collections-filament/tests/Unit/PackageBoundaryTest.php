<?php

use Liberu\BrowserGame\CollectionsFilament\CollectionsFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CollectionsFilamentServiceProvider::class))->toBeTrue();
});
