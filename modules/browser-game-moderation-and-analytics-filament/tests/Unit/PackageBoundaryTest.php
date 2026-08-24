<?php

use Liberu\BrowserGame\ModerationAndAnalyticsFilament\ModerationAndAnalyticsFilamentServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(ModerationAndAnalyticsFilamentServiceProvider::class))->toBeTrue();
});
