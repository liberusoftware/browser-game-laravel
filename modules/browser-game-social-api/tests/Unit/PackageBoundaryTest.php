<?php

use Liberu\BrowserGame\SocialApi\SocialApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(SocialApiServiceProvider::class))->toBeTrue();
});
