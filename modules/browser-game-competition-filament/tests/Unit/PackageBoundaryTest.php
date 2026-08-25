<?php

use Liberu\BrowserGame\CompetitionFilament\CompetitionFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CompetitionFilamentServiceProvider::class))->toBeTrue();
});
