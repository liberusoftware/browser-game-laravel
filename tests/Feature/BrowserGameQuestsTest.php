<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Quests\Support\QuestsManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-quests/database/migrations'),
        '--realpath' => true,
    ]);
});

it('accepts quests, validates objectives, and records reward completion evidence', function (): void {
    $manager = app(QuestsManager::class);
    $quest = $manager->define('First Hunt', 'first-hunt', ['kills' => 2], ['gold' => 25]);

    $accepted = $manager->accept($quest, 'player-1', [], 'accept-1');
    $partial = $manager->progress($quest, 'player-1', ['kills' => 1], 'in_progress', 'progress-1');
    $completed = $manager->progress($quest, 'player-1', ['kills' => 2], 'completed', 'complete-1');

    expect($accepted->status)->toBe('in_progress')
        ->and($partial->progress['kills'])->toBe(1)
        ->and($completed->status)->toBe('completed')
        ->and($completed->completion_count)->toBe(1)
        ->and($completed->reward_claimed_at)->not->toBeNull();
});

it('enforces prerequisites and permits a repeatable quest to be completed again', function (): void {
    $manager = app(QuestsManager::class);
    $prior = $manager->define('Prior Quest', 'prior-quest', ['done' => 1]);
    $quest = $manager->define('Repeat Hunt', 'repeat-hunt', ['wins' => 1], [], true);
    $quest->update(['prerequisites' => ['prior-quest']]);

    expect(fn (): mixed => $manager->accept($quest, 'player-2'))->toThrow(ValidationException::class);
    $manager->accept($prior, 'player-2');
    $manager->progress($prior, 'player-2', ['done' => 1], 'completed', 'prior-complete');
    $manager->accept($quest, 'player-2');
    $first = $manager->progress($quest, 'player-2', ['wins' => 1], 'completed', 'complete-a');
    $second = $manager->progress($quest, 'player-2', ['wins' => 1], 'completed', 'complete-b');

    expect($first->completion_count)->toBe(1)->and($second->completion_count)->toBe(2);
});

it('does not trust client supplied prerequisite completion and keeps completion retries idempotent', function (): void {
    $manager = app(QuestsManager::class);
    $quest = $manager->define('Protected Hunt', 'protected-hunt', ['wins' => 1], [], true);
    $quest->update(['prerequisites' => ['missing-quest']]);

    expect(fn (): mixed => $manager->accept($quest, 'player-3', ['completed_quests' => ['missing-quest']]))
        ->toThrow(ValidationException::class);

    $quest->update(['prerequisites' => []]);
    $manager->accept($quest, 'player-3');
    $first = $manager->progress($quest, 'player-3', ['wins' => 1], 'completed', 'same-completion');
    $retry = $manager->complete($quest, 'player-3', 'same-completion');

    expect($retry->getKey())->toBe($first->getKey())->and($retry->completion_count)->toBe(1);
});

it('keeps quest progress isolated by tenant and team', function (): void {
    $manager = app(QuestsManager::class);
    $teamOne = $manager->define('Shared Quest', 'shared-quest-one', ['wins' => 1], teamId: 'team-1', tenantId: 'tenant-1');
    $teamTwo = $manager->define('Shared Quest', 'shared-quest-two', ['wins' => 1], teamId: 'team-2', tenantId: 'tenant-2');

    $manager->accept($teamOne, 'player-1');
    $manager->progress($teamOne, 'player-1', ['wins' => 1], 'completed', 'scoped-completion');
    $other = $manager->accept($teamTwo, 'player-1');

    expect($other->getKey())->not->toBeNull()
        ->and($other->status)->toBe('in_progress');
});
