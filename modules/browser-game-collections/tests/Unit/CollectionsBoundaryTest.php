<?php

it('keeps the browser-game-collections core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/CollectionsManager.php'))->not->toContain('App\\');
});
