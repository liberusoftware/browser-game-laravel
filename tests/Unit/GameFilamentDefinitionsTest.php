<?php

use App\Filament\Admin\Resources\AchievementResource;
use App\Filament\Admin\Resources\GameResourceResource;
use App\Filament\Admin\Resources\GuildResource;
use App\Filament\Admin\Resources\ItemResource;
use App\Filament\Admin\Resources\MenuResource;
use App\Filament\Admin\Resources\ModuleResource;
use App\Filament\Admin\Resources\PlayerItemResource;
use App\Filament\Admin\Resources\PlayerResource;
use App\Filament\Admin\Resources\QuestResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\GameStatsOverview;
use App\Filament\Admin\Widgets\ItemTypeChart;
use App\Filament\Admin\Widgets\LeaderboardWidget;
use App\Filament\Admin\Widgets\PlayerLevelChart;
use App\Filament\Admin\Widgets\PlayerProgressWidget;
use App\Filament\Admin\Widgets\QuickActionsWidget;
use App\Filament\Admin\Widgets\RecentAchievementsWidget;
use App\Filament\Admin\Widgets\RecentPlayersTable;

uses('Illuminate\\Foundation\\Testing\\RefreshDatabase');

it('builds every game resource definition', function () {
    $resources = [
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
    ];

    foreach ($resources as $resource) {
        $schema = 'Filament\\Schemas\\Schema'::make();

        expect($resource::form($schema)->getComponents())->not->toBeEmpty()
            ->and($resource::getPages())->not->toBeEmpty();

        $resource::getRelations();

        if ($resource === ModuleResource::class) {
            expect($resource::getEloquentQuery()->get())->not->toBeEmpty();
        }
    }
});

it('builds every game admin widget definition', function () {
    $widgets = [
        GameStatsOverview::class,
        ItemTypeChart::class,
        LeaderboardWidget::class,
        PlayerLevelChart::class,
        PlayerProgressWidget::class,
        QuickActionsWidget::class,
        RecentAchievementsWidget::class,
        RecentPlayersTable::class,
    ];

    foreach ($widgets as $widget) {
        $instance = app($widget);
        expect($instance)->toBeInstanceOf($widget);

        foreach (['getStats', 'getData', 'getColumns'] as $method) {
            if (! method_exists($instance, $method)) {
                continue;
            }

            $reflection = new ReflectionMethod($instance, $method);
            $reflection->setAccessible(true);
            $reflection->invoke($instance);
        }
    }
});
