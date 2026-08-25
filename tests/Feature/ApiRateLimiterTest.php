<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

it('registers the application API rate limiter outside the package source', function (): void {
    expect(RateLimiter::limiter('api'))->toBeCallable();
});
