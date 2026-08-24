<?php

it('keeps the browser-game-commerce core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/CommerceManager.php'))->not->toContain('App\\');
});
