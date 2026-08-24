<?php

it('keeps the browser-game-live-ops core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/LiveOpsManager.php'))->not->toContain('App\\');
});
