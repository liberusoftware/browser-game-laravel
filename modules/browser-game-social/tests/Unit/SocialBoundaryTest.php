<?php

it('keeps the browser-game-social core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/SocialManager.php'))->not->toContain('App\\');
});
