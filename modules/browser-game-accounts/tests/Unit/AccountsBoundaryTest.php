<?php

it('keeps the browser-game-accounts core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/AccountsManager.php'))->not->toContain('App\\');
});
