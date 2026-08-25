<?php

use Liberu\BrowserGame\CompetitionLivewire\CompetitionLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CompetitionLivewireServiceProvider::class))->toBeTrue();
});
