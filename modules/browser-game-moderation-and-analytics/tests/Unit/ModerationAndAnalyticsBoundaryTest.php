<?php

it('keeps the browser-game-moderation-and-analytics core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/ModerationAndAnalyticsManager.php'))->not->toContain('App\\');
});
