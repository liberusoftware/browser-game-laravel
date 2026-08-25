<?php

it('keeps the browser-game-crafting core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/CraftingManager.php'))->not->toContain('App\\');
});
