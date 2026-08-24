<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Characters\Support\CharactersManager;

uses(RefreshDatabase::class);

it('progresses characters and restores vitals on level-up', function (): void {
    $manager = app(CharactersManager::class);
    $character = $manager->create('player-1', 'Ada', 'human', 'mage');
    $updated = $manager->awardExperience($character, 250);

    expect($updated->level)->toBe(3)
        ->and($updated->stat_points)->toBe(10)
        ->and($updated->available_skill_points)->toBe(10)
        ->and($updated->health)->toBe($updated->max_health)
        ->and($updated->mana)->toBe($updated->max_mana);
});

it('enforces stat point budgets and respec validation', function (): void {
    $manager = app(CharactersManager::class);
    $character = $manager->awardExperience($manager->create('player-2', 'Grace', 'human', 'rogue'), 100);

    $updated = $manager->spendStatPoints($character, ['agility' => 3]);

    expect($updated->agility)->toBe(13)->and($updated->stat_points)->toBe(2);
    expect(fn () => $manager->spendStatPoints($updated, ['strength' => 3]))->toThrow(ValidationException::class);
    expect(fn () => $manager->respec($updated, ['general' => -1]))->toThrow(ValidationException::class);
});
