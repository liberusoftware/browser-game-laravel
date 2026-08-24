<?php

use App\Events\GuildInvitationSent;
use App\Events\GuildMemberJoined;
use App\Events\GuildMemberLeft;
use App\Filament\Admin\Resources\GameResourceResource\Pages\EditGameResource;
use App\Filament\Admin\Resources\GameResourceResource\Pages\ListGameResources;
use App\Filament\Admin\Resources\GameResourceResource\Pages\ViewGameResource;
use App\Filament\Admin\Resources\GuildResource\Pages\EditGuild;
use App\Filament\Admin\Resources\GuildResource\Pages\ListGuilds;
use App\Filament\Admin\Resources\GuildResource\Pages\ViewGuild;
use App\Filament\Admin\Resources\ItemResource\Pages\EditItem;
use App\Filament\Admin\Resources\ItemResource\Pages\ListItems;
use App\Filament\Admin\Resources\ItemResource\Pages\ViewItem;
use App\Filament\Admin\Resources\MenuResource\Pages\EditMenu;
use App\Filament\Admin\Resources\MenuResource\Pages\ListMenus;
use App\Filament\Admin\Resources\MenuResource\Pages\ViewMenu;
use App\Filament\Admin\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\EditPlayerItem;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\ListPlayerItems;
use App\Filament\Admin\Resources\PlayerItemResource\Pages\ViewPlayerItem;
use App\Filament\Admin\Resources\PlayerResource\Pages\EditPlayer;
use App\Filament\Admin\Resources\PlayerResource\Pages\ListPlayers;
use App\Filament\Admin\Resources\PlayerResource\Pages\ViewPlayer;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\AchievementsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\QuestsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ResourcesRelationManager;
use App\Filament\Admin\Resources\QuestResource\Pages\EditQuest;
use App\Filament\Admin\Resources\QuestResource\Pages\ListQuests;
use App\Filament\Admin\Resources\QuestResource\Pages\ViewQuest;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
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
use App\Livewire\CombatArena;
use App\Livewire\CraftingWorkshop;
use App\Livewire\DailyRewardClaim;
use App\Livewire\LeaderboardPanel;
use App\Livewire\Marketplace;
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
use App\Notifications\GuildInvitationNotification;
use App\Notifications\LevelUpNotification;
use App\Notifications\QuestCompletedNotification;
use App\Services\MenuService;
use App\Services\RankingService;
use App\Services\TeamManagementService;
use App\Settings\GameSettings;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

it('creates and assigns a personal team through the team service', function () {
    $user = User::factory()->create(['name' => 'Coverage User']);
    $team = app(TeamManagementService::class)->createPersonalTeamForUser($user);

    expect($team->name)->toBe("Coverage User's Team")
        ->and($user->fresh()->current_team_id)->toBe($team->id);
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
        ->assertStatus(200);

    Livewire::test(Marketplace::class)
        ->call('selectItemToSell', 999999)
        ->call('createListing')
        ->call('purchaseItem', 999999)
        ->call('cancelListing', 999999)
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
});
