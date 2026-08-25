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
    $quest = $manager->define('Repeat Hunt', 'repeat-hunt', ['wins' => 1], [], true);
    $quest->update(['prerequisites' => ['prior-quest']]);

    expect(fn (): mixed => $manager->accept($quest, 'player-2'))->toThrow(ValidationException::class);
    $manager->accept($quest, 'player-2', ['completed_quests' => ['prior-quest']]);
    $first = $manager->progress($quest, 'player-2', ['wins' => 1], 'completed', 'complete-a');
    $second = $manager->progress($quest, 'player-2', ['wins' => 1], 'completed', 'complete-b');

    expect($first->completion_count)->toBe(1)->and($second->completion_count)->toBe(2);
});
