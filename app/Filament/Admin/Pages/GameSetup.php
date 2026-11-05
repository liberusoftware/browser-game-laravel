<?php

namespace App\Filament\Admin\Pages;

use App\Settings\GameSettings;
use Database\Seeders\NpcSeeder;
use Database\Seeders\StarterAdventureSeeder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class GameSetup extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Overview';

    protected static ?string $navigationLabel = 'Game setup wizard';

    protected static ?int $navigationSort = -20;

    protected string $view = 'filament.admin.pages.game-setup';

    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->getSchema('form')->fill(app(GameSettings::class)->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Wizard::make([
                Step::make('Welcome your players')
                    ->description('Choose how new adventures begin.')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Toggle::make('player_registration_enabled')->label('Allow player registration'),
                        TextInput::make('starting_level')->numeric()->integer()->minValue(1)->maxValue(100)->required(),
                        TextInput::make('starting_gold')->numeric()->integer()->minValue(0)->maxValue(1000000)->required(),
                        TextInput::make('starting_experience')->numeric()->integer()->minValue(0)->maxValue(1000000)->required(),
                    ]),
                Step::make('Set the pace')
                    ->description('Balance progression and inventory limits.')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        TextInput::make('max_player_level')->numeric()->integer()->minValue(fn ($get) => (int) $get('starting_level'))->maxValue(100)->required(),
                        TextInput::make('experience_multiplier')->numeric()->integer()->minValue(1)->maxValue(10)->required()->helperText('1 is the standard progression rate.'),
                        TextInput::make('gold_multiplier')->numeric()->integer()->minValue(1)->maxValue(10)->required(),
                        TextInput::make('max_inventory_size')->numeric()->integer()->minValue(1)->maxValue(1000)->required(),
                        TextInput::make('max_active_quests')->numeric()->integer()->minValue(1)->maxValue(100)->required(),
                    ]),
                Step::make('Choose the experience')
                    ->description('Review your choices before saving. You can revisit this wizard anytime.')
                    ->icon('heroicon-o-flag')
                    ->schema([
                        Toggle::make('pvp_enabled')->label('Enable player versus player combat'),
                        Toggle::make('guild_system_enabled')->label('Enable guilds'),
                    ]),
            ])->submitAction(view('filament.admin.pages.setup-submit')),
        ]);
    }

    public function installStarterAdventure(): void
    {
        abort_unless(static::canAccess(), 403);
        app(StarterAdventureSeeder::class)->run();
        app(NpcSeeder::class)->run();
        Notification::make()->title('Dawnvale starter adventure installed')
            ->body(count(config('starter-adventure.quests')).' quests, '.count(config('starter-adventure.items')).' items, and '.count(config('starter-adventure.classes')).' classes and '.count(config('arena.npcs')).' NPC opponents are ready. Existing content and progress are preserved.')
            ->success()->send();
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $state = $this->getSchema('form')->getState();
        $settings = app(GameSettings::class);
        $settings->fill($state);
        $settings->save();
        Notification::make()->title('Game settings saved')->body('Your configuration is ready. Install the Dawnvale starter adventure below, or add your own quests and items in Game Content.')->success()->send();
    }
}
