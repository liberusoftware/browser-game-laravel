<?php

use Liberu\BrowserGame\ModerationAndAnalyticsApi\ModerationAndAnalyticsApiServiceProvider;

it('autoloads the package service provider', function (): void {
    expect(class_exists(ModerationAndAnalyticsApiServiceProvider::class))->toBeTrue();
});
