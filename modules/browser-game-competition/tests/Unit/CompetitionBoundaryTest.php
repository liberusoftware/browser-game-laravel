<?php

it('keeps the browser-game-competition core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/CompetitionManager.php'))->not->toContain('App\\');
});
