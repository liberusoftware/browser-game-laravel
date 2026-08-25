<?php

it('keeps the quests core independent of the host application', function (): void {
    expect(file_get_contents(__DIR__.'/../../src/Support/QuestsManager.php'))->not->toContain('App\\');
});
