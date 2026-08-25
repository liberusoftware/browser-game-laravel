<?php

declare(strict_types=1);

use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Livewire\GuildPanel;
use App\Livewire\PlayerDashboard;
use App\Livewire\PlayerInventory;
use App\Livewire\QuestBoard;
use App\Models\GameNotification;
use App\Models\Guild;
use App\Models\Item;
use App\Models\MarketplaceListing;
use App\Models\Player;
use App\Models\Player_Item;
use App\Models\Quest;
use App\Models\Recipe;
use App\Models\RecipeMaterial;
use App\Models\User;
use App\Notifications\GuildInvitationNotification;
use App\Notifications\LevelUpNotification;
use App\Services\CraftingService;
use App\Services\DailyRewardService;
use App\Services\MarketplaceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('covers successful and alternate legacy crafting paths', function (): void {
    $player = Player::factory()->create(['level' => 1]);
    $material = Item::factory()->create();
    $result = Item::factory()->create();
    $recipe = Recipe::create([
        'name' => 'Coverage Recipe', 'description' => 'Coverage', 'result_item_id' => $result->id,
        'min_level' => 5, 'result_quantity' => 2, 'success_rate' => 100, 'crafting_time_seconds' => 0,
    ]);
    RecipeMaterial::create(['recipe_id' => $recipe->id, 'item_id' => $material->id, 'quantity' => 1]);
    $service = app(CraftingService::class);

    expect($service->craftItem($player, $recipe)['success'])->toBeFalse();
    $service->learnRecipe($player, $recipe);
    expect($service->craftItem($player, $recipe)['success'])->toBeFalse();
    $player->update(['level' => 10]);
    expect($service->craftItem($player, $recipe)['success'])->toBeFalse();
    $player->playerItems()->create(['item_id' => $material->id, 'quantity' => 1]);
    expect($service->craftItem($player, $recipe)['success'])->toBeTrue();
    $player->playerItems()->create(['item_id' => $material->id, 'quantity' => 1]);
    expect($service->craftItem($player, $recipe)['success'])->toBeTrue();
});

it('covers legacy model relationships, stale streaks, and notification channels', function (): void {
    $player = Player::factory()->create();
    $player->dailyRewards()->create([
        'reward_date' => Carbon::now()->subDays(3), 'day_streak' => 4,
        'gold_rewarded' => 0, 'experience_rewarded' => 0, 'items_rewarded' => [],
    ]);
    expect(app(DailyRewardService::class)->getCurrentStreak($player))->toBe(0);

    $guild = Guild::factory()->create();
    $inviter = Player::factory()->create();
    $notification = new GuildInvitationNotification($guild, $inviter);
    expect($notification->via($player))->toBe(['mail', 'database']);
    expect((new LevelUpNotification(2, 1))->via($player))->toBe(['mail', 'database']);
    expect((new Player_Item())->player())->toBeInstanceOf(BelongsTo::class)
        ->and((new RecipeMaterial())->recipe())->toBeInstanceOf(BelongsTo::class);
    $player->profile_photo_path = 'https://example.test/avatar.png';
    expect($player->email)->not->toBeNull();
    expect(User::factory()->create(['profile_photo_path' => 'https://example.test/photo.png'])->profile_photo_url)
        ->toBe('https://example.test/photo.png');
});

it('covers marketplace inventory, seller, and cancellation branches', function (): void {
    $item = Item::factory()->create();
    $seller = Player::factory()->create();
    $buyer = Player::factory()->create();
    $seller->playerItems()->create(['item_id' => $item->id, 'quantity' => 1]);
    $listing = app(MarketplaceService::class)->createListing($seller, $item, 1, 10);
    $buyer->resources()->create(['resource_type' => 'gold', 'quantity' => 20]);

    expect(app(MarketplaceService::class)->purchaseListing($buyer, $listing)['success'])->toBeTrue();
    expect($seller->fresh()->resources()->where('resource_type', 'gold')->value('quantity'))->toBe(10);

    $seller->playerItems()->create(['item_id' => $item->id, 'quantity' => 1]);
    $cancelled = MarketplaceListing::create([
        'seller_id' => $seller->id, 'item_id' => $item->id, 'quantity' => 1,
        'price_per_unit' => 10, 'status' => 'active',
    ]);
    expect(app(MarketplaceService::class)->cancelListing($seller, $cancelled))->toBeTrue();
    expect($seller->fresh()->playerItems()->where('item_id', $item->id)->value('quantity'))->toBe(2);
});

it('covers livewire demo fallbacks and successful mutations', function (): void {
    Livewire::test(PlayerDashboard::class)->assertStatus(200);
    Livewire::test(PlayerInventory::class)->assertStatus(200);
    Livewire::test(QuestBoard::class)->assertStatus(200);
    Livewire::test(GuildPanel::class)->assertStatus(200);

    $player = Player::factory()->create(['level' => 1, 'experience' => 0]);
    $item = Item::factory()->create();
    $player->playerItems()->create(['item_id' => $item->id, 'quantity' => 2]);
    $player->resources()->create(['resource_type' => 'gold', 'quantity' => 5]);
    Livewire::test(PlayerInventory::class)
        ->call('useItem', $item->id)
        ->call('useItem', $item->id)
        ->call('dropItem', $item->id)
        ->assertStatus(200);

    $quest = Quest::factory()->create(['item_reward_id' => $item->id, 'experience_reward' => 100]);
    $component = Livewire::test(QuestBoard::class)
        ->call('acceptQuest', $quest->id)
        ->call('completeQuest', $quest->id);
    expect($component->get('player')->fresh()->items()->where('item_id', $item->id)->exists())->toBeTrue();
});

it('covers authenticated notification and marketplace controller responses', function (): void {
    $controller = app(NotificationController::class);
    $user = User::factory()->create();
    $request = Request::create('/notifications', 'GET');
    $request->setUserResolver(fn (): User => $user);
    expect($controller->index($request)->getStatusCode())->toBe(404)
        ->and($controller->unread($request)->getStatusCode())->toBe(404)
        ->and($controller->count($request)->getStatusCode())->toBe(404)
        ->and($controller->markAllAsRead($request)->getStatusCode())->toBe(404);

    $player = Player::factory()->create(['email' => $user->email]);
    $user->unsetRelation('player');
    GameNotification::create(['player_id' => $player->id, 'type' => 'test', 'title' => 'Test', 'message' => 'Test', 'is_read' => false]);
    expect($controller->index($request)->getStatusCode())->toBe(200)
        ->and($controller->unread($request)->getStatusCode())->toBe(200)
        ->and($controller->count($request)->getStatusCode())->toBe(200)
        ->and($controller->markAllAsRead($request)->getStatusCode())->toBe(200);

    $marketplace = app(MarketplaceController::class);
    $apiRequest = Request::create('/marketplace', 'GET');
    $apiRequest->setUserResolver(fn (): User => $user);
    $marketItem = Item::factory()->create();
    expect(fn () => $marketplace->store(Request::create('/marketplace', 'POST', [
        'item_id' => $marketItem->id, 'quantity' => 1, 'price_per_unit' => 10,
    ])))->toThrow(HttpException::class);
    $apiRequest->setUserResolver(fn (): User => $user);
    $player->playerItems()->create(['item_id' => $marketItem->id, 'quantity' => 1]);
    $storeRequest = Request::create('/marketplace', 'POST', [
        'item_id' => $marketItem->id, 'quantity' => 1, 'price_per_unit' => 10,
    ]);
    $storeRequest->setUserResolver(fn (): User => $user);
    expect($marketplace->store($storeRequest)->getStatusCode())->toBe(201);
    expect($marketplace->index($apiRequest)->getStatusCode())->toBe(200);
});
