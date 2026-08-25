<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Characters\Support\CharactersManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-characters/database/migrations'),
        '--realpath' => true,
    ]);
});

it('allocates skills from a bounded budget and makes retries idempotent', function (): void {
    $manager = app(CharactersManager::class);
    $character = $manager->awardExperience($manager->create('player-1', 'Ada', 'human', 'mage'), 100);

    $updated = $manager->allocateSkills($character, ['arcana' => 2], 'skill-operation-1');
    $retry = $manager->allocateSkills($updated, ['arcana' => 2], 'skill-operation-1');

    expect($updated->skills['arcana'])->toBe(2)
        ->and($updated->available_skill_points)->toBe(3)
        ->and($retry->skills['arcana'])->toBe(2)
        ->and($retry->available_skill_points)->toBe(3);

    expect(fn (): mixed => $manager->allocateSkills($retry, ['arcana' => 4]))
        ->toThrow(ValidationException::class);
});

it('enforces respec budgets, updates profiles, and bounds vitals', function (): void {
    $manager = app(CharactersManager::class);
    $character = $manager->awardExperience($manager->create('player-2', 'Grace', 'human', 'rogue'), 100);
    $updated = $manager->respec($character, ['stealth' => 3]);

    expect($updated->available_skill_points)->toBe(2)
        ->and($updated->respec_count)->toBe(1);

    $profile = $manager->updateProfile($updated, 'Grace Night', 'elf', 'ranger', 'scout');
    expect($profile->name)->toBe('Grace Night')->and($profile->race)->toBe('elf');

    expect(fn (): mixed => $manager->updateVitals($profile, 111, 0))
        ->toThrow(ValidationException::class);
    $vitals = $manager->updateVitals($profile, 40, 12);
    expect($vitals->health)->toBe(40)->and($vitals->mana)->toBe(12);
});

it('scopes duplicate character names by tenant and team', function (): void {
    $manager = app(CharactersManager::class);
    $first = $manager->create('player-1', 'Shared Name', 'human', 'mage', teamId: 'team-1', tenantId: 'tenant-1');
    $second = $manager->create('player-1', 'Shared Name', 'human', 'mage', teamId: 'team-2', tenantId: 'tenant-2');

    expect($second->getKey())->not->toBe($first->getKey())
        ->and(fn (): mixed => $manager->create('player-1', 'Shared Name', 'human', 'mage', teamId: 'team-1', tenantId: 'tenant-1'))
        ->toThrow(ValidationException::class);
});
