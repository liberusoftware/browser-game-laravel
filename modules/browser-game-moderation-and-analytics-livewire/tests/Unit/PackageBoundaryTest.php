<?php

use Liberu\BrowserGame\ModerationAndAnalyticsLivewire\ModerationAndAnalyticsLivewireServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(ModerationAndAnalyticsLivewireServiceProvider::class))->toBeTrue();
});
