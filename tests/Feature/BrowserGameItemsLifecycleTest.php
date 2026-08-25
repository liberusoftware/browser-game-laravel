<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Items\Support\ItemsManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate', [
        '--path' => base_path('modules/browser-game-items/database/migrations'),
        '--realpath' => true,
    ]);
});

it('enforces active definitions, stack limits, equipment slots, and durability bounds', function (): void {
    $manager = app(ItemsManager::class);
    $sword = $manager->define('Sword', ['slot' => 'weapon', 'max_stack' => 2, 'max_durability' => 10]);
    $otherSword = $manager->define('Other Sword', ['slot' => 'weapon', 'max_durability' => 10]);
    $first = $manager->addToInventory('player-1', $sword, 2, ['source' => 'quest']);

    expect(fn (): mixed => $manager->addToInventory('player-1', $sword))
        ->toThrow(ValidationException::class);

    $second = $manager->addToInventory('player-1', $otherSword);
    $manager->equip('player-1', $first);
    $manager->equip('player-1', $second);

    expect($first->fresh()->equipment_slot)->toBeNull()
        ->and($second->fresh()->equipment_slot)->toBe('weapon')
        ->and($second->fresh()->provenance)->toBe([]);

    $durable = $manager->adjustDurability('player-1', $second, -100);
    expect($durable->durability)->toBe(0);
    $repaired = $manager->adjustDurability('player-1', $second, 100);
    expect($repaired->durability)->toBe(10);
});

it('rejects deep container cycles while preserving provenance updates', function (): void {
    $manager = app(ItemsManager::class);
    $container = $manager->define('Bag', ['type' => 'container']);
    $nested = $manager->define('Chest', ['type' => 'container']);
    $item = $manager->define('Gem');
    $bag = $manager->addToInventory('player-2', $container);
    $chest = $manager->addToInventory('player-2', $nested);
    $gem = $manager->addToInventory('player-2', $item, 1, ['source' => 'drop']);

    $manager->putInContainer('player-2', $chest, $bag);
    $manager->putInContainer('player-2', $gem, $chest);
    $updated = $manager->setProvenance('player-2', $gem, ['source' => 'verified-drop']);

    expect($updated->provenance['source'])->toBe('verified-drop');
    expect(fn (): mixed => $manager->putInContainer('player-2', $bag, $chest))
        ->toThrow(ValidationException::class);
});
