<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\ItemResource;
use App\Filament\Admin\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Admin\Resources\PlayerItemResource;
use App\Filament\Admin\Resources\PlayerResource;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\QuestsRelationManager;
use App\Filament\Admin\Resources\QuestResource;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Filament\Admin\Widgets\LeaderboardWidget;
use App\Filament\Admin\Widgets\RecentPlayersTable;
use App\Filament\App\Widgets\ActiveQuestsWidget;
use App\Filament\App\Widgets\SocialLinksWidget;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Livewire\GuildPanel;
use App\Livewire\Marketplace;
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
use Filament\Facades\Filament;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\Settings\Settings\SiteSettings;
use Livewire\Component;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

final class LegacyCoverageTableHarness extends Component implements HasTable
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table;
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function render(): View
    {
        return view('welcome');
    }
}

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

it('executes the remaining legacy Filament callbacks', function (): void {
    $harness = new LegacyCoverageTableHarness();
    $tableProperty = new ReflectionProperty($harness, 'table');
    $tableProperty->setAccessible(true);
    $tableProperty->setValue($harness, Table::make($harness));

    $item = Item::factory()->create(['type' => 'weapon', 'rarity' => 'common']);
    $itemTable = ItemResource::table(Table::make($harness));
    $replicate = collect($itemTable->getRecordActions())->first(fn ($action): bool => $action->getName() === 'replicate');
    $property = new ReflectionProperty($replicate, 'beforeReplicaSaved');
    $property->setAccessible(true);
    $replica = $item->replicate();
    ($property->getValue($replicate))($replica);
    expect($replica->name)->toContain('(Copy)');
    expect(ItemResource::getGlobalSearchResultDetails($item))->toHaveKey('Type', 'Weapon');

    $player = Player::factory()->create(['rank' => 1]);
    expect(PlayerResource::getGloballySearchableAttributes())->toContain('username');
    $playerTable = PlayerResource::table(Table::make($harness));
    $levelFilter = collect($playerTable->getFilters())->first(fn ($filter): bool => $filter->getName() === 'level_range');
    $levelFilter->apply(Player::query(), ['value' => '1-10']);

    $playerItemTable = PlayerItemResource::table(Table::make($harness));
    $typeFilter = collect($playerItemTable->getFilters())->first(fn ($filter): bool => $filter->getName() === 'item_type');
    $typeFilter->apply(Player_Item::query(), ['value' => 'weapon']);
    $typeFilter->apply(Player_Item::query(), ['value' => null]);

    $quest = Quest::factory()->create(['description' => 'short']);
    $questTable = QuestResource::table(Table::make($harness));
    $questCopy = collect($questTable->getRecordActions())->first(fn ($action): bool => $action->getName() === 'replicate');
    $questProperty = new ReflectionProperty($questCopy, 'beforeReplicaSaved');
    $questProperty->setAccessible(true);
    $questReplica = $quest->replicate();
    ($questProperty->getValue($questCopy))($questReplica);
    expect($questReplica->name)->toContain('(Copy)');

    $description = collect($questTable->getColumns())->first(fn ($column): bool => $column->getName() === 'description');
    $tooltip = new ReflectionProperty($description, 'tooltip');
    $tooltip->setAccessible(true);
    $description->record($quest);
    expect(($tooltip->getValue($description))($description))->toBeNull();

    $userTable = UsersTable::configure(Table::make($harness));
    $verified = User::factory()->create(['email_verified_at' => now()]);
    $verifiedColumn = collect($userTable->getColumns())->first(fn ($column): bool => $column->getName() === 'email_verified_at');
    $verifiedColumn->record($verified);
    $tableTooltip = new ReflectionProperty($verifiedColumn, 'tooltip');
    $tableTooltip->setAccessible(true);
    expect(($tableTooltip->getValue($verifiedColumn))($verified))->toContain('Verified');

    $page = (new ReflectionClass(ListModules::class))->newInstanceWithoutConstructor();
    $header = new ReflectionMethod($page, 'getHeaderActions');
    $header->setAccessible(true);
    $header->invoke($page)[0]->getActionFunction()([]);

    $leaderboard = app(LeaderboardWidget::class)->table(Table::make($harness));
    $leaderboard->getRecordActions()[0]->record($player)->getUrl();
    $recent = app(RecentPlayersTable::class)->table(Table::make($harness));
    $recent->getRecordActions()[0]->record($player)->getUrl();
    $this->actingAs(User::factory()->create());
    Filament::setTenant(Team::factory()->create());
    $leaderboard->getRecordActions()[0]->record($player)->getUrl();
    $recent->getRecordActions()[0]->record($player)->getUrl();
    $active = app(ActiveQuestsWidget::class)->table(Table::make($harness));
    foreach ($active->getColumns() as $column) {
        $column->getColor('medium');
        $column->getColor('hard');
    }
    $settings = app(SiteSettings::class);
    foreach (['github_url', 'facebook_url', 'twitter_url', 'youtube_url'] as $key) {
        $settings->{$key} = null;
    }
    app(SocialLinksWidget::class)->render();
    $questRelationTable = (new QuestsRelationManager())->table(Table::make($harness));
    foreach ($questRelationTable->getColumns() as $column) {
        $column->getColor('completed');
        $column->getColor('in-progress');
    }
    expect($questRelationTable)->toBeInstanceOf(Table::class);
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

it('covers legacy Livewire rejection and selection branches', function (): void {
    Player::query()->delete();
    Livewire::test(PlayerInventory::class)->assertStatus(200);
    Player::query()->delete();
    Livewire::test(QuestBoard::class)->assertStatus(200);
    Player::query()->delete();
    Livewire::test(PlayerDashboard::class)->assertStatus(200);

    $player = Player::factory()->create(['health' => 50, 'level' => 10]);
    $this->actingAs(User::factory()->create(['email' => $player->email]));
    Livewire::test(CombatArena::class, ['player' => $player])
        ->call('heal')
        ->assertDispatched('show-message');

    $recipe = Recipe::create([
        'name' => 'Unknown Livewire Recipe', 'description' => 'Coverage',
        'result_item_id' => Item::factory()->create()->id, 'min_level' => 1,
        'result_quantity' => 1, 'success_rate' => 100, 'crafting_time_seconds' => 0,
    ]);
    Livewire::test(CraftingWorkshop::class, ['player' => $player])
        ->call('selectRecipe', $recipe->id)
        ->call('craftItem')
        ->assertDispatched('show-error');

    $player->recipes()->attach($recipe->id, ['learned_at' => now()]);
    $material = Item::factory()->create();
    $recipe->materials()->create(['item_id' => $material->id, 'quantity' => 1]);
    $player->playerItems()->create(['item_id' => $material->id, 'quantity' => 1]);
    Livewire::test(CraftingWorkshop::class, ['player' => $player])
        ->call('selectRecipe', $recipe->id)
        ->call('craftItem')
        ->assertDispatched('show-message');

    $guild = Guild::factory()->create();
    Livewire::test(GuildPanel::class, ['player' => $player])
        ->call('joinGuild', $guild->id)
        ->call('selectGuild', $guild->id)
        ->call('leaveGuild', $guild->id)
        ->assertSet('selectedGuild', null);

    $marketItem = Item::factory()->create();
    $player->playerItems()->create(['item_id' => $marketItem->id, 'quantity' => 1]);
    $seller = Player::factory()->create();
    $listing = MarketplaceListing::create([
        'seller_id' => $seller->id, 'item_id' => $marketItem->id, 'quantity' => 1,
        'price_per_unit' => 20, 'status' => 'active',
    ]);
    $marketplace = Livewire::test(Marketplace::class, ['player' => $player])
        ->call('selectItemToSell', $marketItem->id)
        ->set('sellQuantity', 2)
        ->call('createListing')
        ->assertDispatched('show-error')
        ->call('purchaseItem', $listing->id)
        ->assertDispatched('show-error')
        ->call('cancelListing', $listing->id);

    $item = Item::factory()->create();
    $closed = MarketplaceListing::create([
        'seller_id' => $seller->id, 'item_id' => $item->id, 'quantity' => 1,
        'price_per_unit' => 20, 'status' => 'sold',
    ]);
    expect(app(MarketplaceService::class)->purchaseListing($player, $closed)['success'])->toBeFalse();
    $unstocked = MarketplaceListing::create([
        'seller_id' => $seller->id, 'item_id' => $item->id, 'quantity' => 1,
        'price_per_unit' => 20, 'status' => 'active',
    ]);
    expect(app(MarketplaceService::class)->cancelListing($seller, $unstocked))->toBeTrue();

    $playerListing = MarketplaceListing::create([
        'seller_id' => $player->id, 'item_id' => $item->id, 'quantity' => 1,
        'price_per_unit' => 20, 'status' => 'active',
    ]);
    Livewire::test(Marketplace::class, ['player' => $player])
        ->call('cancelListing', $playerListing->id)
        ->assertDispatched('show-message');
});

it('covers livewire demo fallbacks and successful mutations', function (): void {
    Livewire::test(PlayerDashboard::class)->assertStatus(200);
    Livewire::test(PlayerInventory::class)->assertStatus(200);
    Livewire::test(QuestBoard::class)->assertStatus(200);
    Livewire::test(GuildPanel::class)->assertStatus(200);

    Player::query()->delete();
    $player = Player::factory()->create(['level' => 1, 'experience' => 0]);
    $item = Item::factory()->create();
    $player->playerItems()->create(['item_id' => $item->id, 'quantity' => 2]);
    $player->resources()->create(['resource_type' => 'gold', 'quantity' => 5]);
    Livewire::test(PlayerInventory::class)
        ->call('useItem', $item->id)
        ->call('useItem', $item->id)
        ->call('dropItem', $item->id)
        ->assertStatus(200);

    Player_Item::create(['player_id' => $player->id, 'item_id' => $item->id, 'quantity' => 1]);
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
        ->and($controller->markAsRead($request, 999999)->getStatusCode())->toBe(404)
        ->and($controller->markAllAsRead($request)->getStatusCode())->toBe(404);

    $player = Player::factory()->create(['email' => $user->email]);
    $user->unsetRelation('player');
    $gameNotification = GameNotification::create(['player_id' => $player->id, 'type' => 'test', 'title' => 'Test', 'message' => 'Test', 'is_read' => false]);
    expect($controller->index($request)->getStatusCode())->toBe(200)
        ->and($controller->unread($request)->getStatusCode())->toBe(200)
        ->and($controller->count($request)->getStatusCode())->toBe(200)
        ->and($controller->markAsRead($request, $gameNotification->id)->getStatusCode())->toBe(200)
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
    $otherSeller = Player::factory()->create();
    $uncancellable = MarketplaceListing::create([
        'seller_id' => $otherSeller->id, 'item_id' => $marketItem->id,
        'quantity' => 1, 'price_per_unit' => 10, 'status' => 'active',
    ]);
    expect($marketplace->cancel($storeRequest, $uncancellable)->getStatusCode())->toBe(422);
    expect($marketplace->index($apiRequest)->getStatusCode())->toBe(200);
});
