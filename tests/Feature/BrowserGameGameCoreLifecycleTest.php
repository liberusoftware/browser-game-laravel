<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\GameCore\Events\GameWorldUpdated;
use Liberu\BrowserGame\GameCore\Queries\GameCoreOverview;
use Liberu\BrowserGame\GameCore\Support\ArrayGameCoreContext;
use Liberu\BrowserGame\GameCore\Support\GameCoreManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-game-core/database/migrations'),
        '--realpath' => true,
    ]);
});

it('enforces actor and context scope across world lifecycle mutations', function (): void {
    $manager = app(GameCoreManager::class);
    $context = new ArrayGameCoreContext(actor: 'actor-1', tenant: 'tenant-1', team: 'team-1');
    $world = $manager->createWorld($context, 'World', 'world');

    Event::fake();
    $updated = $manager->updateWorld($context, $world, 'Updated World', 'active', ['region' => 'north']);

    expect($updated->status)->toBe('active')->and($updated->metadata)->toBe(['region' => 'north']);
    Event::assertDispatched(GameWorldUpdated::class);
    expect(fn (): mixed => $manager->updateWorld(new ArrayGameCoreContext(actor: 'actor-2', tenant: 'tenant-1', team: 'team-2'), $world, 'Denied', 'active'))
        ->toThrow(ValidationException::class);
});

it('rejects world creation without an actor', function (): void {
    expect(fn (): mixed => app(GameCoreManager::class)->createWorld(new ArrayGameCoreContext(actor: null, tenant: 'tenant-1', team: 'team-1'), 'World', 'world'))
        ->toThrow(ValidationException::class);
});

it('evaluates feature flags with constraints, deterministic rollout, and world overrides', function (): void {
    $manager = app(GameCoreManager::class);
    $context = new ArrayGameCoreContext(actor: 'actor-1', tenant: 'tenant-1', team: 'team-1');
    $world = $manager->createWorld($context, 'World', 'world');
    $manager->setFeatureFlag($context, null, 'global-event', true, 100, ['team_id' => 'team-1']);
    $manager->setFeatureFlag($context, $world, 'world-event', true, 100);
    $manager->setFeatureFlag($context, null, 'overridden-event', true, 100);
    $manager->setFeatureFlag($context, $world, 'overridden-event', false, 100);
    $manager->setFeatureFlag($context, $world, 'disabled-world-event', false, 100);

    $overview = app(GameCoreOverview::class);

    expect($overview->isEnabled($context, $world, 'global-event'))->toBeTrue()
        ->and($overview->isEnabled($context, $world, 'world-event'))->toBeTrue()
        ->and($overview->isEnabled($context, $world, 'overridden-event'))->toBeFalse()
        ->and($overview->isEnabled($context, $world, 'disabled-world-event'))->toBeFalse()
        ->and($overview->isEnabled(new ArrayGameCoreContext(actor: 'actor-2', tenant: 'tenant-1', team: 'team-2'), $world, 'global-event'))->toBeFalse();
});
