<?php

it('keeps the combat core free of application coupling', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/CombatManager.php'))->not->toContain('App\\');
});
