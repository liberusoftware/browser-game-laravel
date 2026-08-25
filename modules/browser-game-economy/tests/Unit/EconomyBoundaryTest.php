<?php

it('keeps the browser-game-economy core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/EconomyManager.php'))->not->toContain('App\\');
});
