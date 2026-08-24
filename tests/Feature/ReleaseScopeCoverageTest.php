<?php

use App\Events\GuildInvitationSent;
use App\Events\GuildMemberJoined;
use App\Events\GuildMemberLeft;
use App\Filament\Admin\Resources\AchievementResource;
use App\Filament\Admin\Resources\GameResourceResource;
use App\Filament\Admin\Resources\GameResourceResource\Pages\EditGameResource;
use App\Filament\Admin\Resources\GameResourceResource\Pages\ListGameResources;
use App\Filament\Admin\Resources\GameResourceResource\Pages\ViewGameResource;
use App\Filament\Admin\Resources\GuildResource;
use App\Filament\Admin\Resources\GuildResource\Pages\EditGuild;
use App\Filament\Admin\Resources\GuildResource\Pages\ListGuilds;
use App\Filament\Admin\Resources\GuildResource\Pages\ViewGuild;
use App\Filament\Admin\Resources\ItemResource;
use App\Filament\Admin\Resources\ItemResource\Pages\EditItem;
use App\Filament\Admin\Resources\ItemResource\Pages\ListItems;
use App\Filament\Admin\Resources\ItemResource\Pages\ViewItem;
use App\Filament\Admin\Resources\MenuResource;
use App\Filament\Admin\Resources\MenuResource\Pages\EditMenu;
use App\Filament\Admin\Resources\MenuResource\Pages\ListMenus;
use App\Filament\Admin\Resources\MenuResource\Pages\ViewMenu;
use App\Filament\Admin\Resources\ModuleResource;
use App\Filament\Admin\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Admin\Resources\ModuleResource\Pages\ViewModule;
use App\Filament\Admin\Resources\PlayerItemResource;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\EditPlayerItem;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\ListPlayerItems;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\ViewPlayerItem;
use App\Filament\Admin\Resources\PlayerResource;
use App\Filament\Admin\Resources\PlayerResource\Pages\EditPlayer;
use App\Filament\Admin\Resources\PlayerResource\Pages\ListPlayers;
use App\Filament\Admin\Resources\PlayerResource\Pages\ViewPlayer;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\AchievementsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\QuestsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ResourcesRelationManager;
use App\Filament\Admin\Resources\QuestResource;
use App\Filament\Admin\Resources\QuestResource\Pages\EditQuest;
use App\Filament\Admin\Resources\QuestResource\Pages\ListQuests;
use App\Filament\Admin\Resources\QuestResource\Pages\ViewQuest;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\ContentStatsChart;
use App\Filament\Admin\Widgets\GameStatsOverview;
use App\Filament\Admin\Widgets\ItemTypeChart;
use App\Filament\Admin\Widgets\LeaderboardWidget;
use App\Filament\Admin\Widgets\PlayerLevelChart;
use App\Filament\Admin\Widgets\PlayerProgressWidget;
use App\Filament\Admin\Widgets\QuickActionsWidget;
use App\Filament\Admin\Widgets\RecentAchievementsWidget;
use App\Filament\Admin\Widgets\RecentPlayersTable;
use App\Filament\App\Widgets\ActiveQuestsWidget;
use App\Filament\App\Widgets\InventoryWidget;
use App\Filament\App\Widgets\PlayerStatsWidget;
use App\Filament\App\Widgets\SocialLinksWidget;
use App\Http\Controllers\QuestController;
use App\Livewire\CombatArena;
use App\Livewire\CraftingWorkshop;
use App\Livewire\DailyRewardClaim;
use App\Livewire\GuildPanel;
use App\Livewire\LeaderboardPanel;
use App\Livewire\Marketplace;
use App\Livewire\PlayerDashboard;
use App\Livewire\PlayerInventory;
use App\Livewire\QuestBoard;
use App\Models\Achievement;
use App\Models\Battle;
use App\Models\DailyReward;
use App\Models\Guild;
use App\Models\Guild_Membership;
use App\Models\Item;
use App\Models\Leaderboard;
use App\Models\MarketplaceListing;
use App\Models\Menu;
use App\Models\Player;
use App\Models\Player_Profile;
use App\Models\Player_Quest;
use App\Models\PlayerEquipment;
use App\Models\PlayerSkill;
use App\Models\Quest;
use App\Models\Recipe;
use App\Models\RecipeMaterial;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\User;
use App\Notifications\AchievementUnlockedNotification;
use App\Notifications\GuildInvitationNotification;
use App\Notifications\LevelUpNotification;
use App\Notifications\QuestCompletedNotification;
use App\Services\DailyRewardService;
use App\Services\CraftingService;
use App\Services\MarketplaceService;
use App\Services\MenuService;
use App\Services\QuestService;
use App\Services\RankingService;
use App\Services\TeamManagementService;
use App\Settings\GameSettings;
use Carbon\Carbon;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Liberu\Foundation\Settings\Settings\SiteSettings;
use Livewire\Component;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class ReleaseScopeTableHarness extends Component implements HasTable
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

it('executes the game model relationship contracts', function () {
    $models = [
        new DailyReward(),
        new Guild_Membership(),
        new Item(),
        new Leaderboard(),
        new Menu(),
        new PlayerEquipment(),
        new PlayerSkill(),
        new Player_Profile(),
        new Player_Quest(),
        new Quest(),
        new Recipe(),
        new RecipeMaterial(),
        new Resource(),
        new Skill(),
    ];

    foreach ($models as $model) {
        expect($model->getRelations())->toBeArray();
    }

    $player = new Player();
    $guild = new Guild();
    $item = new Item();
    $quest = new Quest();
    $recipe = new Recipe();
    $battle = new Battle();
    $listing = new MarketplaceListing();
    $achievement = new Achievement();

    foreach ([
        (new DailyReward())->player(),
        (new Guild_Membership())->player(),
        (new Guild_Membership())->guild(),
        (new Leaderboard())->player(),
        (new PlayerEquipment())->player(),
        (new PlayerEquipment())->item(),
        (new PlayerSkill())->player(),
        (new PlayerSkill())->skill(),
        (new Player_Profile())->player(),
        (new Player_Quest())->player(),
        (new Player_Quest())->quest(),
        (new Resource())->player(),
        (new Skill())->players(),
        $player->gameNotifications(),
        $player->profile(),
        $player->playerItems(),
        $player->items(),
        $player->playerQuests(),
        $player->quests(),
        $player->resources(),
        $player->guildMemberships(),
        $player->guilds(),
        $player->statistics(),
        $player->achievements(),
        $player->equipment(),
        $player->skills(),
        $player->playerSkills(),
        $player->battlesAsAttacker(),
        $player->battlesAsDefender(),
        $player->recipes(),
        $player->sellerListings(),
        $player->purchases(),
        $player->leaderboardEntries(),
        $player->dailyRewards(),
        $guild->members(),
        $guild->memberships(),
        $item->playerItems(),
        $item->players(),
        $item->questRewards(),
        $quest->itemReward(),
        $quest->players(),
        $recipe->resultItem(),
        $recipe->materials(),
        $recipe->players(),
        $battle->attacker(),
        $battle->defender(),
        $battle->winner(),
        $listing->seller(),
        $listing->buyer(),
        $listing->item(),
        $achievement->players(),
    ] as $relation) {
        expect($relation)->toBeObject();
    }

    expect(GameSettings::group())->toBe('game');
});

it('renders menus and executes game events and notifications', function () {
    $menu = Menu::create(['name' => 'Quests', 'url' => '/quests', 'order' => 1]);
    $html = app(MenuService::class)->buildMenu()->toHtml();
    expect($html)->toContain('Quests')->toContain('/quests');

    $player = new Player(['id' => 7, 'username' => 'Hero', 'level' => 3]);
    $guild = new Guild(['id' => 8, 'name' => 'Explorers']);
    $inviter = new Player(['id' => 9, 'username' => 'Leader']);
    $guild->setAttribute('id', 8);

    $invitation = new GuildInvitationSent($player, $guild, $inviter);
    expect($invitation->player)->toBe($player);

    $joined = new GuildMemberJoined($guild, $player);
    $left = new GuildMemberLeft($guild, $player);
    expect($joined->broadcastAs())->toBe('guild.member-joined')
        ->and($left->broadcastAs())->toBe('guild.member-left')
        ->and($joined->broadcastWith()['player_username'])->toBe('Hero')
        ->and($left->broadcastWith()['guild_name'])->toBe('Explorers');

    $user = new User(['name' => 'Hero', 'email' => 'hero@example.com']);
    expect((new GuildInvitationNotification($guild, $inviter))->toArray($player))
        ->toHaveKey('guild_id', 8)
        ->and((new LevelUpNotification(4, 3))->toArray($user)['new_level'])->toBe(4)
        ->and((new QuestCompletedNotification($menu, 10))->via($user))->toContain('mail');

    (new QuestCompletedNotification($menu, 10))->toMail($player);
    (new QuestCompletedNotification($menu))->toMail($player);
    (new QuestCompletedNotification($menu, 10))->toArray($player);
    (new AchievementUnlockedNotification('First Quest', 'A description'))->toMail($player);
    (new AchievementUnlockedNotification('First Quest'))->toMail($player);
    (new AchievementUnlockedNotification('First Quest'))->toArray($player);
    (new GuildInvitationNotification($guild, $inviter))->toMail($user);
    (new LevelUpNotification(4, 3))->toMail($user);
});

it('covers player aggregate helpers and quest-board branches', function () {
    $item = Item::factory()->create([
        'strength_bonus' => 2,
        'defense_bonus' => 3,
        'agility_bonus' => 4,
        'intelligence_bonus' => 5,
        'health_bonus' => 6,
        'mana_bonus' => 7,
    ]);
    $player = Player::factory()->create([
        'health' => 50,
        'max_health' => 100,
        'mana' => 25,
        'max_mana' => 50,
        'strength' => 1,
        'defense' => 1,
        'agility' => 1,
        'intelligence' => 1,
    ]);
    $player->equipment()->create(['item_id' => $item->id, 'slot' => 'weapon']);
    expect($player->battles()->toBase()->get()->toArray())->toBeArray()
        ->and($player->getTotalStats()['total']['strength'])->toBe(3);

    $quest = Quest::factory()->create(['experience_reward' => 100, 'item_reward_id' => $item->id]);
    Livewire::test(QuestBoard::class)
        ->call('acceptQuest', 999999)
        ->call('acceptQuest', $quest->id)
        ->call('completeQuest', $quest->id)
        ->call('completeQuest', 999999)
        ->call('abandonQuest', 999999)
        ->call('refreshQuests')
        ->assertStatus(200);
});

it('covers empty-state branches for legacy game panels and settings links', function () {
    Livewire::test(GuildPanel::class)
        ->call('selectGuild', 999999)
        ->call('joinGuild', 999999)
        ->call('leaveGuild', 999999)
        ->call('refreshGuilds')
        ->assertStatus(200);

    Livewire::test(PlayerInventory::class)
        ->call('useItem', 999999)
        ->call('dropItem', 999999)
        ->call('refreshInventory')
        ->assertStatus(200);

    Livewire::test(PlayerDashboard::class)
        ->call('handleQuestCompleted', 999999, 250)
        ->assertStatus(200);

    $settings = app(SiteSettings::class);
    $settings->github_url = 'https://github.com/example';
    $settings->facebook_url = 'https://facebook.com/example';
    $settings->twitter_url = 'https://x.com/example';
    $settings->youtube_url = 'https://youtube.com/example';
    Livewire::test(SocialLinksWidget::class)->assertStatus(200);
});

it('runs the player ranking command through its service contract', function () {
    $ranking = Mockery::mock(RankingService::class);
    $ranking->shouldReceive('recalculateScores')->once()->andReturn(2);
    $ranking->shouldReceive('updateAllRankings')->once()->andReturn(2);
    app()->instance(RankingService::class, $ranking);

    $this->artisan('players:update-rankings')
        ->expectsOutput('Updated scores for 2 players.')
        ->expectsOutput('Updated rankings for 2 players.')
        ->assertSuccessful();
});

it('runs the inventory benchmark command with a minimal fixture', function () {
    $this->artisan('inventory:benchmark', ['--players' => 1, '--items' => 1])
        ->assertSuccessful();
});

it('creates and assigns a personal team through the team service', function () {
    $user = User::factory()->create(['name' => 'Coverage User']);
    $team = app(TeamManagementService::class)->createPersonalTeamForUser($user);

    expect($team->name)->toBe("Coverage User's Team")
        ->and($user->fresh()->current_team_id)->toBe($team->id);
});

it('covers default-team assignment and crafting outcomes', function () {
    $service = app(TeamManagementService::class);
    $user = User::factory()->create(['name' => 'Default Team User']);
    $service->assignUserToDefaultTeam($user);
    $service->assignUserToDefaultTeam($user);

    $player = Player::factory()->create(['level' => 10]);
    $material = Item::factory()->create();
    $result = Item::factory()->create();
    $recipe = Recipe::create([
        'name' => 'Known Recipe',
        'description' => 'Coverage recipe',
        'result_item_id' => $result->id,
        'min_level' => 1,
        'result_quantity' => 1,
        'success_rate' => 100,
        'crafting_time_seconds' => 0,
    ]);
    RecipeMaterial::create(['recipe_id' => $recipe->id, 'item_id' => $material->id, 'quantity' => 1]);
    app(CraftingService::class)->learnRecipe($player, $recipe);
    $player->playerItems()->create(['item_id' => $material->id, 'quantity' => 1]);
    expect(app(CraftingService::class)->craftItem($player, $recipe)['success'])->toBeTrue();

    $failedRecipe = $recipe->replicate();
    $failedRecipe->name = 'Failed Recipe';
    $failedRecipe->success_rate = 0;
    $failedRecipe->save();
    $player->playerItems()->create(['item_id' => $material->id, 'quantity' => 1]);
    app(CraftingService::class)->learnRecipe($player, $failedRecipe);
    expect(app(CraftingService::class)->craftItem($player, $failedRecipe)['success'])->toBeFalse();
});

it('covers direct player notification controller responses', function () {
    $player = Player::factory()->create();
    $request = Request::create('/api/notifications', 'GET');
    $request->setUserResolver(fn () => $player);
    $controller = app(\App\Http\Controllers\NotificationController::class);

    expect($controller->index($request)->getStatusCode())->toBe(200)
        ->and($controller->unread($request)->getStatusCode())->toBe(200)
        ->and($controller->count($request)->getStatusCode())->toBe(200)
        ->and($controller->markAllAsRead($request)->getStatusCode())->toBe(200)
        ->and($controller->markAsRead($request, 999999)->getStatusCode())->toBe(404);
});

it('covers marketplace service success and rejection paths', function () {
    $item = Item::factory()->create();
    $seller = Player::factory()->create();
    $buyer = Player::factory()->create();
    $sellerItem = $seller->playerItems()->create(['item_id' => $item->id, 'quantity' => 3]);

    $service = app(MarketplaceService::class);
    expect($service->createListing($seller, $item, 5, 20))->toBeNull();

    $listing = $service->createListing($seller, $item, 2, 20);
    expect($listing)->toBeInstanceOf(MarketplaceListing::class)
        ->and($sellerItem->fresh()->quantity)->toBe(1);

    expect($service->purchaseListing($seller, $listing)['success'])->toBeFalse()
        ->and($service->purchaseListing($buyer, $listing)['success'])->toBeFalse();

    $buyer->resources()->create(['resource_type' => 'gold', 'quantity' => 100]);
    expect($service->purchaseListing($buyer, $listing)['success'])->toBeTrue()
        ->and($buyer->playerItems()->where('item_id', $item->id)->value('quantity'))->toBe(2);

    $cancelListing = MarketplaceListing::create([
        'seller_id' => $seller->id,
        'item_id' => $item->id,
        'quantity' => 1,
        'price_per_unit' => 10,
        'status' => 'active',
    ]);
    expect($service->cancelListing($buyer, $cancelListing))->toBeFalse()
        ->and($service->cancelListing($seller, $cancelListing))->toBeTrue();
});

it('covers daily rewards, streaks, bonuses, and duplicate claims', function () {
    Carbon::setTestNow('2026-08-24 12:00:00');
    Item::factory()->count(8)->create();
    $player = Player::factory()->create(['experience' => 0]);
    $service = app(DailyRewardService::class);

    expect($service->canClaimReward($player))->toBeTrue()
        ->and($service->getCurrentStreak($player))->toBe(0);

    $reward = $service->claimDailyReward($player);
    expect($reward->day_streak)->toBe(1)
        ->and($service->claimDailyReward($player))->toBeNull();

    $player->dailyRewards()->update(['reward_date' => Carbon::yesterday(), 'day_streak' => 6]);
    $weekly = $service->claimDailyReward($player);
    expect($weekly->day_streak)->toBe(7)->and($weekly->items_rewarded)->not->toBeEmpty();

    $player->dailyRewards()->delete();
    $player->dailyRewards()->create([
        'reward_date' => Carbon::yesterday(),
        'day_streak' => 29,
        'gold_rewarded' => 0,
        'experience_rewarded' => 0,
        'items_rewarded' => [],
    ]);
    $monthly = $service->claimDailyReward($player);
    expect($monthly->day_streak)->toBe(30)->and($monthly->items_rewarded)->toHaveCount(1);

    Carbon::setTestNow();
});

it('covers quest service and controller success and error responses', function () {
    $player = Player::factory()->create(['email' => 'quest-api@example.com', 'level' => 1, 'experience' => 0]);
    $user = User::factory()->create(['email' => $player->email]);
    $quest = Quest::factory()->create(['experience_reward' => 100]);
    $controller = app(QuestController::class);
    $request = Request::create('/api/quests', 'GET');
    $request->setUserResolver(fn () => $user);

    expect($controller->available($request)->getStatusCode())->toBe(200)
        ->and($controller->active($request)->getStatusCode())->toBe(200)
        ->and($controller->completed($request)->getStatusCode())->toBe(200);

    expect($controller->accept($request, $quest)->getStatusCode())->toBe(200)
        ->and($controller->accept($request, $quest)->getStatusCode())->toBe(400)
        ->and($controller->complete($request, $quest)->getStatusCode())->toBe(200)
        ->and($controller->complete($request, $quest)->getStatusCode())->toBe(400)
        ->and($controller->abandon($request, $quest)->getStatusCode())->toBe(400);

    $secondQuest = Quest::factory()->create();
    app(QuestService::class)->acceptQuest($player, $secondQuest);
    expect($controller->abandon($request, $secondQuest)->getStatusCode())->toBe(200);
});

it('builds the registered Filament page actions and infolists', function () {
    $pages = [
        EditGameResource::class,
        ListGameResources::class,
        ViewGameResource::class,
        EditGuild::class,
        ListGuilds::class,
        ViewGuild::class,
        EditItem::class,
        ListItems::class,
        ViewItem::class,
        EditMenu::class,
        ListMenus::class,
        ViewMenu::class,
        ListModules::class,
        ViewModule::class,
        EditPlayerItem::class,
        ListPlayerItems::class,
        ViewPlayerItem::class,
        EditPlayer::class,
        ListPlayers::class,
        ViewPlayer::class,
        EditQuest::class,
        ListQuests::class,
        ViewQuest::class,
        EditUser::class,
        ListUsers::class,
    ];

    foreach ($pages as $page) {
        $instance = (new ReflectionClass($page))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($page, 'getHeaderActions');
        $method->setAccessible(true);

        expect($method->invoke($instance))->toBeArray();
    }

    $viewUser = (new ReflectionClass(ViewUser::class))
        ->newInstanceWithoutConstructor();
    $infolist = new ReflectionMethod($viewUser, 'infolist');
    $infolist->setAccessible(true);
    expect($infolist->invoke($viewUser, 'Filament\\Schemas\\Schema'::make())->getComponents())
        ->not->toBeEmpty();
});

it('executes the remaining gameplay components and their empty-state branches', function () {
    $player = Player::factory()->create([
        'health' => 5,
        'max_health' => 100,
        'email' => 'coverage-player@example.com',
    ]);
    $user = User::factory()->create(['email' => $player->email]);
    $this->actingAs($user);

    Livewire::test(LeaderboardPanel::class)
        ->call('selectCategory', 'wealth')
        ->assertSet('selectedCategory', 'wealth')
        ->assertStatus(200);

    Livewire::test(CombatArena::class)
        ->call('viewBattleLog')
        ->call('startPvEBattle')
        ->assertDispatched('show-message');

    $player->update(['health' => 5]);
    $player->resources()->create(['resource_type' => 'gold', 'quantity' => 100]);
    Livewire::test(CombatArena::class)
        ->call('heal')
        ->assertDispatched('show-message');
    $player->update(['health' => 100]);
    Livewire::test(CombatArena::class)
        ->call('startPvEBattle')
        ->assertDispatched('battle-completed');

    $resultItem = Item::factory()->create();
    $recipe = Recipe::create([
        'name' => 'Coverage Recipe',
        'description' => 'Coverage fixture',
        'result_item_id' => $resultItem->id,
        'min_level' => 1,
        'result_quantity' => 1,
        'success_rate' => 100,
        'crafting_time_seconds' => 0,
    ]);
    Livewire::test(CraftingWorkshop::class)
        ->call('selectRecipe', $recipe->id)
        ->call('craftItem')
        ->assertDispatched('show-error');

    Livewire::test(DailyRewardClaim::class)
        ->call('refreshRewardStatus')
        ->call('claimReward')
        ->call('claimReward')
        ->assertStatus(200);

    $marketItem = Item::factory()->create(['sell_price' => 25]);
    $player->playerItems()->create(['item_id' => $marketItem->id, 'quantity' => 2]);
    $otherPlayer = Player::factory()->create();
    $otherPlayer->resources()->create(['resource_type' => 'gold', 'quantity' => 100]);
    $otherUser = User::factory()->create(['email' => $otherPlayer->email]);
    $listing = MarketplaceListing::create([
        'seller_id' => $otherPlayer->id,
        'item_id' => $marketItem->id,
        'quantity' => 1,
        'price_per_unit' => 10,
        'status' => 'active',
    ]);

    Livewire::test(Marketplace::class)
        ->call('selectItemToSell', $marketItem->id)
        ->set('sellQuantity', 1)
        ->call('createListing')
        ->set('searchTerm', $marketItem->name)
        ->call('purchaseItem', $listing->id)
        ->call('cancelListing', $listing->id)
        ->assertStatus(200);

    $this->actingAs($otherUser);
    Livewire::test(Marketplace::class)
        ->call('cancelListing', $listing->id)
        ->assertStatus(200);
});

it('renders the registered admin and app widgets', function () {
    Player::factory()->create(['email' => 'widget-player@example.com']);
    $user = User::factory()->create(['email' => 'widget-player@example.com']);
    $this->actingAs($user);

    foreach ([
        ContentStatsChart::class,
        GameStatsOverview::class,
        ItemTypeChart::class,
        LeaderboardWidget::class,
        PlayerLevelChart::class,
        PlayerProgressWidget::class,
        QuickActionsWidget::class,
        RecentAchievementsWidget::class,
        RecentPlayersTable::class,
        ActiveQuestsWidget::class,
        InventoryWidget::class,
        PlayerStatsWidget::class,
        SocialLinksWidget::class,
    ] as $widget) {
        Livewire::test($widget)->assertStatus(200);
    }
});

it('builds the registered relation-manager and user table definitions', function () {
    foreach ([
        AchievementResource::class,
        GameResourceResource::class,
        GuildResource::class,
        ItemResource::class,
        MenuResource::class,
        ModuleResource::class,
        PlayerItemResource::class,
        PlayerResource::class,
        QuestResource::class,
        UserResource::class,
    ] as $resource) {
        expect($resource::table(Table::make(new ReleaseScopeTableHarness())))
            ->toBeInstanceOf(Table::class);
    }

    $table = Table::make(new ReleaseScopeTableHarness());

    foreach ([
        AchievementsRelationManager::class,
        ItemsRelationManager::class,
        QuestsRelationManager::class,
        ResourcesRelationManager::class,
    ] as $managerClass) {
        $manager = (new ReflectionClass($managerClass))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($managerClass, 'table');
        $method->setAccessible(true);

        expect($method->invoke($manager, $table))->toBeInstanceOf(Table::class);
    }

    expect(UsersTable::configure($table))
        ->toBeInstanceOf(Table::class);

    $itemTable = ItemResource::table(Table::make(new ReleaseScopeTableHarness()));
    $itemColumns = collect($itemTable->getColumns());
    $typeColumn = $itemColumns->first(fn ($column) => $column->getName() === 'type');
    $rarityColumn = $itemColumns->first(fn ($column) => $column->getName() === 'rarity');
    foreach (['weapon', 'armor', 'consumable', 'material', 'quest', 'other'] as $value) {
        $typeColumn->getColor($value);
    }
    foreach (['common', 'uncommon', 'rare', 'epic', 'legendary', 'other'] as $value) {
        $rarityColumn->getColor($value);
    }

    $item = Item::factory()->create(['type' => 'weapon', 'rarity' => 'rare']);
    expect(ItemResource::getGlobalSearchResultDetails($item))->toHaveKeys(['Type', 'Rarity']);
    expect(PlayerResource::getGlobalSearchResultTitle($player = Player::factory()->create()))
        ->toBe($player->username)
        ->and(PlayerResource::getGlobalSearchResultDetails($player))->toHaveKeys(['Email', 'Level']);

    foreach (PlayerResource::table(Table::make(new ReleaseScopeTableHarness()))->getFilters() as $filter) {
        foreach ([null, '1-10', '11-25', '26-50', '51-100'] as $value) {
            $filter->apply(Player::query(), ['value' => $value]);
        }
    }

    $playerTable = PlayerResource::table(Table::make(new ReleaseScopeTableHarness()));
    $rankColumn = collect($playerTable->getColumns())->first(fn ($column) => $column->getName() === 'rank');
    foreach ([1, 5, 11, null] as $rank) {
        $rankColumn->getColor($rank);
        $rankColumn->formatState($rank);
    }

    foreach ([ItemsRelationManager::class, ResourcesRelationManager::class] as $managerClass) {
        $manager = (new ReflectionClass($managerClass))->newInstanceWithoutConstructor();
        $managerTable = (new ReflectionMethod($managerClass, 'table'))->invoke($manager, $table);
        foreach ($managerTable->getColumns() as $column) {
            if ($column->getName() === 'item.type') {
                foreach (['weapon', 'armor', 'consumable', 'material', 'quest', 'other'] as $value) {
                    $column->getColor($value);
                }
            }
            if ($column->getName() === 'item.rarity') {
                foreach (['common', 'uncommon', 'rare', 'epic', 'legendary', 'other'] as $value) {
                    $column->getColor($value);
                }
            }
            if ($column->getName() === 'resource_type') {
                foreach (['gold', 'wood', 'stone', 'iron', 'food', 'energy', 'gems', 'other'] as $value) {
                    $column->getColor($value);
                }
            }
        }
    }
});
