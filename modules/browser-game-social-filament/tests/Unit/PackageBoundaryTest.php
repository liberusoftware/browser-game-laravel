<?php

use Liberu\BrowserGame\SocialFilament\SocialFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(SocialFilamentServiceProvider::class))->toBeTrue();
});
