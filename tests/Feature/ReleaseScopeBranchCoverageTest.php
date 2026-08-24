<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\GameResourceResource;
use App\Filament\Admin\Resources\GuildResource;
use App\Filament\Admin\Resources\ItemResource;
use App\Filament\Admin\Resources\MenuResource;
use App\Filament\Admin\Resources\ModuleResource;
use App\Filament\Admin\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Admin\Resources\PlayerItemResource;
use App\Filament\Admin\Resources\PlayerResource;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\QuestsRelationManager;
use App\Filament\Admin\Resources\PlayerResource\RelationManagers\ResourcesRelationManager;
use App\Filament\Admin\Resources\QuestResource;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Filament\Admin\Widgets\ContentStatsChart;
use App\Filament\Admin\Widgets\LeaderboardWidget;
use App\Filament\Admin\Widgets\RecentPlayersTable;
use App\Filament\App\Widgets\ActiveQuestsWidget;
use App\Filament\App\Widgets\InventoryWidget;
use App\Filament\App\Widgets\PlayerStatsWidget;
use App\Filament\App\Widgets\SocialLinksWidget;
use App\Livewire\CombatArena;
use App\Livewire\CraftingWorkshop;
use App\Livewire\Marketplace;
use App\Livewire\PlayerInventory;
use App\Livewire\QuestBoard;
use App\Models\Guild;
use App\Models\Item;
use App\Models\Menu;
use App\Models\Player;
use App\Models\Player_Item;
use App\Models\Quest;
use App\Models\Recipe;
use App\Models\RecipeMaterial;
use App\Models\User;
use App\Http\Controllers\NotificationController;
use App\Notifications\GuildInvitationNotification;
use App\Notifications\LevelUpNotification;
use App\Services\CraftingService;
use App\Services\DailyRewardService;
use App\Services\InventoryService;
use App\Services\MarketplaceService;
use App\Services\MenuService;
use App\Services\NotificationService;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class ReleaseScopeBranchTableHarness extends Component implements HasTable
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

function invokeReleaseScopeMethod(object $object, string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($object, ...$arguments);
}

it('executes representative Filament table and widget branches', function (): void {
    foreach ([
        ['resource' => GameResourceResource::class, 'values' => ['gold', 'wood', 'stone', 'iron', 'food', 'energy', 'gems', 'other']],
        ['resource' => ItemResource::class, 'values' => ['weapon', 'armor', 'consumable', 'material', 'quest', 'other']],
        ['resource' => PlayerResource::class, 'values' => [1, 5, 11, null]],
        ['resource' => QuestResource::class, 'values' => ['easy', 'medium', 'hard', 'other']],
    ] as $definition) {
        $table = $definition['resource']::table(Table::make(new ReleaseScopeBranchTableHarness()));

        foreach ($table->getColumns() as $column) {
            foreach ($definition['values'] as $value) {
                $column->getColor($value);
                $column->formatState(10);
            }
        }
    }

    $menuTable = MenuResource::table(Table::make(new ReleaseScopeBranchTableHarness()));
    foreach ($menuTable->getFilters() as $filter) {
        $filter->apply(Menu::query(), []);
    }

    $moduleTable = ModuleResource::table(Table::make(new ReleaseScopeBranchTableHarness()));
    foreach ($moduleTable->getFilters() as $filter) {
        $filter->apply(ModuleResource::getEloquentQuery(), ['value' => true]);
        $filter->apply(ModuleResource::getEloquentQuery(), ['value' => false]);
    }

    $player = Player::factory()->create(['level' => 98]);
    $action = collect(PlayerResource::table(Table::make(new ReleaseScopeBranchTableHarness()))->getFlatBulkActions())
        ->first(fn ($candidate): bool => $candidate->getName() === 'adjustLevel');
    $action->getActionFunction()(['level_change' => 5], collect([$player]));
    expect($player->fresh()->level)->toBe(100);

    Item::factory()->create(['type' => 'unknown']);
    foreach ([ContentStatsChart::class, LeaderboardWidget::class, RecentPlayersTable::class] as $widget) {
        $instance = app($widget);
        if (method_exists($instance, 'getData')) {
            invokeReleaseScopeMethod($instance, 'getData');
        }
        if (method_exists($instance, 'table')) {
            $instance->table(Table::make(new ReleaseScopeBranchTableHarness()));
        }
    }

    $stats = app(PlayerStatsWidget::class);
    invokeReleaseScopeMethod($stats, 'getStats');
    Player::factory()->create()->setRelation('quests', collect());
    invokeReleaseScopeMethod($stats, 'getStats');

    $inventory = app(InventoryWidget::class);
    expect($inventory->getViewData()['maxSlots'])->toBe(20);
    $activeQuests = app(ActiveQuestsWidget::class)->table(Table::make(new ReleaseScopeBranchTableHarness()));
    foreach ($activeQuests->getColumns() as $column) {
        $column->getColor('easy');
        $column->getColor('completed');
        $column->getColor('unknown');
    }
});

it('executes invalid and alternate gameplay component branches', function (): void {
    $player = Player::factory()->create(['health' => 5]);
    $user = User::factory()->create(['email' => $player->email]);
    Auth::login($user);

    Livewire::test(CombatArena::class, ['player' => $player])
        ->call('startPvEBattle')
        ->call('viewBattleLog');

    $recipe = Recipe::create([
        'name' => 'Coverage Recipe',
        'description' => 'Coverage recipe',
        'result_item_id' => Item::factory()->create()->id,
        'result_quantity' => 1,
        'min_level' => 1,
        'success_rate' => 100,
    ]);
    Livewire::test(CraftingWorkshop::class, ['player' => $player])
        ->call('craftItem')
        ->call('selectRecipe', $recipe->id)
        ->call('craftItem');

    Livewire::test(Marketplace::class, ['player' => $player])
        ->call('selectItemToSell', 999999)
        ->call('createListing')
        ->call('purchaseItem', 999999)
        ->call('cancelListing', 999999)
        ->set('searchTerm', 'missing')
        ->assertStatus(200);

    Livewire::test(PlayerInventory::class)
        ->call('useItem', 999999)
        ->call('dropItem', 999999)
        ->call('refreshInventory');

    Livewire::test(QuestBoard::class)
        ->call('acceptQuest', 999999)
        ->call('completeQuest', 999999)
        ->call('abandonQuest', 999999)
        ->call('refreshQuests');
});

it('executes release-scope resource forms, relations, actions, and model relations', function (): void {
    $scopeHarness = new ReleaseScopeBranchTableHarness();
    $tableProperty = new ReflectionProperty($scopeHarness, 'table');
    $tableProperty->setAccessible(true);
    $tableProperty->setValue($scopeHarness, Table::make($scopeHarness));

    foreach ([GuildResource::class, ItemResource::class, MenuResource::class, ModuleResource::class, PlayerItemResource::class, PlayerResource::class, QuestResource::class] as $resource) {
        $resource::form(Schema::make());
        $resource::getRelations();
        $resource::getPages();
    }

    $longDescription = str_repeat('description ', 8);
    foreach ([GuildResource::class, ItemResource::class] as $resource) {
        $record = $resource === GuildResource::class
            ? Guild::factory()->create(['description' => $longDescription])
            : Item::factory()->create(['description' => $longDescription]);
        $columns = $resource::table(Table::make($scopeHarness))->getColumns();
        foreach ($columns as $column) {
            if ($column->getName() === 'description') {
                $tooltip = new ReflectionProperty($column, 'tooltip');
                $tooltip->setAccessible(true);
                $callback = $tooltip->getValue($column);
                $column->record($record);
                expect($callback($column))->toBe($longDescription);
                $record->description = 'short';
                $shortColumn = collect($resource::table(Table::make($scopeHarness))->getColumns())
                    ->first(fn ($candidate): bool => $candidate->getName() === 'description');
                $shortColumn->record($record);
                $shortTooltip = new ReflectionProperty($shortColumn, 'tooltip');
                $shortTooltip->setAccessible(true);
                expect($shortTooltip->getValue($shortColumn)($shortColumn))->toBeNull();
            }
        }
    }

    $item = Item::factory()->create(['type' => 'weapon', 'rarity' => 'common']);
    expect(ItemResource::getGloballySearchableAttributes())->toBe(['name', 'type', 'rarity'])
        ->and(ItemResource::getGlobalSearchResultDetails($item))->toBe(['Type' => 'Weapon', 'Rarity' => 'Common']);

    $itemTable = ItemResource::table(Table::make(new ReleaseScopeBranchTableHarness()));
    $replicate = collect($itemTable->getRecordActions())->first(fn ($action): bool => $action->getName() === 'replicate');
    if ($replicate && method_exists($replicate, 'getBeforeReplicaSaved')) {
        $replica = $item->replicate();
        $replicate->getBeforeReplicaSaved()($replica);
        expect($replica->name)->toContain('(Copy)');
    }
    $rarity = collect($itemTable->getFlatBulkActions())->first(fn ($action): bool => $action->getName() === 'updateRarity');
    $rarity?->getActionFunction()(['rarity' => 'legendary'], collect([$item]));
    expect($item->fresh()->rarity)->toBe('legendary');

    MenuResource::getEloquentQuery()->whereNull('parent_id')->get();
    ModuleResource::getEloquentQuery()->get();
    $menu = Menu::factory()->create();
    expect($menu->parent())->toBeInstanceOf(BelongsTo::class)
        ->and($menu->children())->toBeInstanceOf(HasMany::class);

    $player = Player::factory()->create();
    $guild = Guild::factory()->create();
    $player->gameNotifications()->create(['type' => 'test', 'title' => 'Test', 'message' => 'Test']);
    $notification = $player->gameNotifications()->first();
    $notification->player();
    $notification->markAsRead();
    $guild->members();
    $guild->leaders();
    $player->gameNotifications();
    $player->unreadNotifications();
    $player->profile();
    $player->playerItems();
    $player->items();
    $player->playerQuests();
    $player->quests();
    $player->activeQuests();
    $player->completedQuests();
    $player->resources();
    $player->guildMemberships();
    $player->guilds();
    $player->guild();
    $player->statistics();
    $player->achievements();
    $player->equipment();

    foreach ([ItemsRelationManager::class, QuestsRelationManager::class, ResourcesRelationManager::class] as $managerClass) {
        $manager = (new ReflectionClass($managerClass))->newInstanceWithoutConstructor();
        if (method_exists($manager, 'form')) {
            $manager->form(Schema::make());
        }
        $manager->table(Table::make($manager));
    }

    $viewUser = (new ReflectionClass(ViewUser::class))->newInstanceWithoutConstructor();
    invokeReleaseScopeMethod($viewUser, 'getHeaderActions');
    UsersTable::configure(Table::make(new ReleaseScopeBranchTableHarness()));
    invokeReleaseScopeMethod((new ReflectionClass(ListModules::class))->newInstanceWithoutConstructor(), 'getHeaderActions');

    $playerItemTable = PlayerItemResource::table(Table::make($scopeHarness));
    foreach ($playerItemTable->getColumns() as $column) {
        foreach (['weapon', 'armor', 'consumable', 'material', 'quest', 'misc'] as $type) {
            $column->getColor($type);
        }
    }
    $quest = Quest::factory()->create(['experience_reward' => 125]);
    expect(QuestResource::getGloballySearchableAttributes())->toBe(['name', 'description'])
        ->and(QuestResource::getGlobalSearchResultDetails($quest))->toHaveKey('XP Reward');
    $questTable = QuestResource::table(Table::make($scopeHarness));
    foreach ($questTable->getFilters() as $filter) {
        $filter->apply(Quest::query(), []);
    }
    $questCopy = collect($questTable->getRecordActions())->first(fn ($action): bool => $action->getName() === 'replicate');
    if ($questCopy && method_exists($questCopy, 'getBeforeReplicaSaved')) {
        $replica = $quest->replicate();
        $questCopy->getBeforeReplicaSaved()($replica);
        expect($replica->name)->toContain('(Copy)');
    }
    foreach ($questTable->getColumns() as $column) {
        if ($column->getName() !== 'description') {
            continue;
        }
        $property = new ReflectionProperty($column, 'tooltip');
        $property->setAccessible(true);
        $callback = $property->getValue($column);
        $quest->description = $longDescription;
        $column->record($quest);
        expect($callback($column))->toBe($longDescription);
    }

    $moduleQuery = ModuleResource::getEloquentQuery();
    $moduleQuery->paginate();
    $moduleQuery->where('enabled', true)->paginate();
    $moduleQuery->where('enabled', false)->paginate();

    $leaderboard = app(LeaderboardWidget::class)->table(Table::make($scopeHarness));
    $leader = Player::factory()->create(['rank' => 1]);
    foreach ($leaderboard->getColumns() as $column) {
        $column->getColor(1);
        $column->getColor(4);
        $column->formatState(10);
    }
    $recent = app(RecentPlayersTable::class)->table(Table::make($scopeHarness));
    $recentAction = $recent->getRecordActions()[0] ?? null;
    $recentAction?->record($leader)->getUrl();

    $active = app(ActiveQuestsWidget::class)->table(Table::make($scopeHarness));
    $active->getQuery()->get();
    foreach ($active->getColumns() as $column) {
        $column->getColor('in_progress');
        $column->getColor('failed');
    }

    app(SocialLinksWidget::class)->render();
});

it('executes service alternate outcomes', function (): void {
    $player = Player::factory()->create(['level' => 1]);
    $recipe = Recipe::create([
        'name' => 'Level Recipe',
        'description' => 'Level recipe',
        'result_item_id' => Item::factory()->create()->id,
        'result_quantity' => 1,
        'min_level' => 10,
        'success_rate' => 100,
    ]);

    expect(app(CraftingService::class)->craftItem($player, $recipe)['success'])->toBeFalse()
        ->and(app(CraftingService::class)->learnRecipe($player, $recipe))->toBeTrue()
        ->and(app(CraftingService::class)->learnRecipe($player, $recipe))->toBeFalse();

    $player->update(['level' => 10]);
    expect(app(CraftingService::class)->craftItem($player, $recipe)['success'])->toBeTrue();

    expect(app(DailyRewardService::class)->getCurrentStreak($player))->toBe(0);
    $player->dailyRewards()->create([
        'reward_date' => now()->subDay(),
        'day_streak' => 6,
        'gold_rewarded' => 220,
        'experience_rewarded' => 110,
        'items_rewarded' => [],
    ]);
    Item::factory()->create(['id' => 8]);
    $player->playerItems()->create(['item_id' => 8, 'quantity' => 1]);
    expect(app(DailyRewardService::class)->claimDailyReward($player))->not->toBeNull();
    expect(app(InventoryService::class)->updateItemQuantity($player->id, 999999, 1))->toBeNull();
    expect(app(MarketplaceService::class)->createListing($player, Item::factory()->create(), 1, 10))->toBeNull();
    $parent = Menu::create(['name' => 'Parent', 'url' => '/parent', 'order' => 1]);
    Menu::create(['name' => 'Child', 'url' => '/child', 'parent_id' => $parent->id, 'order' => 1]);
    expect((string) app(MenuService::class)->buildMenu())->toContain('/child');
    app(NotificationService::class)->notifyGuildInvitation($player, Guild::factory()->create(), Player::factory()->create());
});
