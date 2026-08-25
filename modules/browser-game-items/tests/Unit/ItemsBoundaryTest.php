<?php

it('keeps the browser-game-items core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/ItemsManager.php'))->not->toContain('App\\');
});
