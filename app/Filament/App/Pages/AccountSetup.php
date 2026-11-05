<?php

namespace App\Filament\App\Pages;

use App\Models\Player;
use App\Models\User;
use App\Services\StarterAdventureService;
use App\Services\TeamManagementService;
use App\Settings\GameSettings;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Liberu\Foundation\Organizations\Models\Team;

class AccountSetup extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Team & collaboration';

    protected static ?int $navigationSort = -10;

    protected static ?string $navigationLabel = 'Account setup';

    protected static ?string $title = 'Prepare for your adventure';

    protected string $view = 'filament.app.pages.account-setup';

    public array $data = [];

    public ?string $newApiToken = null;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        /** @var User $user */
        $user = auth()->user();
        /** @var Team|null $team */
        $team = $user->currentTeam;

        $this->setupForm()->fill([
            'name' => $user->name,
            'character_class' => $user->player->character_class ?? 'warrior',
            'team_name' => $team !== null ? $team->getAttribute('name') : $user->name."'s Team",
            'generate_api_token' => false,
            'api_token_name' => 'My browser game client',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    Step::make('Your profile')
                        ->icon('heroicon-o-user-circle')
                        ->description('Tell us how to address you.')
                        ->schema([
                            TextInput::make('name')->label('Display name')->required()->maxLength(255),
                            Select::make('character_class')
                                ->label('Character class')
                                ->options(array_map(fn (array $class): string => $class['name'], config('starter-adventure.classes')))
                                ->default('warrior')
                                ->rules(['nullable', Rule::in(array_keys(config('starter-adventure.classes')))])
                                ->helperText('Choose your play style: '.implode(', ', array_column(config('starter-adventure.classes'), 'name')).'. See the adventure guide for stats. Your class and starter kit are kept after setup.')
                                ->disabled(fn (): bool => auth()->user()->player?->character_class !== null),
                        ]),
                    Step::make('Your team')
                        ->icon('heroicon-o-user-group')
                        ->description('Give your workspace a name.')
                        ->schema([
                            TextInput::make('team_name')->label('Team name')->required()->maxLength(255),
                        ]),
                    Step::make('Connect your tools')
                        ->icon('heroicon-o-key')
                        ->description('Connect sign-in providers and prepare API access.')
                        ->schema([
                            Checkbox::make('generate_api_token')
                                ->live()
                                ->label('Create an API token for this account')
                                ->helperText('Use this for the game client or a local integration. The token is shown once after saving.'),
                            TextInput::make('api_token_name')
                                ->label('Token name')
                                ->placeholder('My game client')
                                ->required(fn (callable $get): bool => (bool) $get('generate_api_token'))
                                ->maxLength(80)
                                ->visible(fn (callable $get): bool => (bool) $get('generate_api_token')),
                        ]),
                ])->submitAction(view('filament.app.pages.account-setup-submit')),
            ]);
    }

    public function save(): void
    {
        $state = $this->setupForm()->getState();
        DB::transaction(function () use ($state): void {
            /** @var User $user */
            $user = auth()->user();
            /** @var Team|null $team */
            $team = $user->currentTeam;

            if ($team === null) {
                $team = app(TeamManagementService::class)->createPersonalTeamForUser($user);
            }

            $user->forceFill([
                'name' => $state['name'],
                'onboarding_completed_at' => now(),
            ])->save();

            if ((string) $team->getAttribute('user_id') === (string) $user->getKey()) {
                $team->forceFill(['name' => $state['team_name']])->save();
            }

            $settings = app(GameSettings::class);
            if ($settings->player_registration_enabled) {
                $characterClass = $state['character_class'] ?? 'warrior';
                $player = Player::firstOrCreate(['email' => $user->email], [
                    'username' => Str::limit($state['name'], 200, '').'-'.Str::random(8),
                    'password' => Hash::make(Str::random(64)),
                    'level' => $settings->starting_level,
                    'experience' => $settings->starting_experience,
                    'character_class' => $characterClass,
                    ...config('starter-adventure.classes')[$characterClass]['stats'],
                ]);
                app(StarterAdventureService::class)->grantStarterKit($player, $characterClass);
            }

            if (($state['generate_api_token'] ?? false) && DatabaseSchema::hasTable('personal_access_tokens')) {
                $this->newApiToken = $user->createToken($state['api_token_name'])->plainTextToken;
            }
        });

        if ($this->newApiToken === null) {
            $this->redirect(AdventureGuide::getUrl(panel: 'app'));
        }
    }

    public function continueToApp(): void
    {
        $this->redirect(AdventureGuide::getUrl(panel: 'app'));
    }

    private function setupForm(): Schema
    {
        return $this->getSchema('form') ?? throw new \LogicException('Account setup form is not available.');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && ! auth()->user()->hasCompletedOnboarding();
    }
}
