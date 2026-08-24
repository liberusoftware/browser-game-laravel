<?php

use Liberu\BrowserGame\SocialLivewire\SocialLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(SocialLivewireServiceProvider::class))->toBeTrue();
});
