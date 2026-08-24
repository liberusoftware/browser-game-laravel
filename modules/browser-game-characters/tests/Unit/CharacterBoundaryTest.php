<?php

declare(strict_types=1);

use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Liberu\BrowserGame\Characters\Policies\CharacterPolicy;

it('only exposes a character to its player and team', function (): void {
    $character = new GameCharacter(['player_id' => 'player-1', 'team_id' => 'team-1']);
    $policy = app(CharacterPolicy::class);

    expect($policy->view('player-1', 'team-1', $character))->toBeTrue()
        ->and($policy->view('player-2', 'team-1', $character))->toBeFalse()
        ->and($policy->view('player-1', 'team-2', $character))->toBeFalse();
});
