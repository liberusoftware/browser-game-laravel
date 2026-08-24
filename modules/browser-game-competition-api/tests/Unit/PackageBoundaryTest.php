<?php

use Liberu\BrowserGame\CompetitionApi\CompetitionApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(CompetitionApiServiceProvider::class))->toBeTrue();
});
