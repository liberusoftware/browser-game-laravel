<?php

namespace App\Filament\App\Pages;

use App\Models\User;
use App\Services\TeamManagementService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Liberu\Foundation\Organizations\Models\Team;

class AccountSetup extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Account & workspace';

    protected static ?int $navigationSort = -10;

    protected static ?string $navigationLabel = 'Get Started';

    protected static ?string $title = 'Set up your account';

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
                        ->description('Make your account yours.')
                        ->schema([
                            TextInput::make('name')->label('Display name')->required()->maxLength(255),
                        ]),
                    Step::make('Your team')
                        ->icon('heroicon-o-user-group')
                        ->description('Name the workspace you will manage.')
                        ->schema([
                            TextInput::make('team_name')->label('Team name')->required()->maxLength(255),
                        ]),
                    Step::make('Connect your tools')
                        ->icon('heroicon-o-key')
                        ->description('Connect sign-in providers and prepare API access.')
                        ->schema([
                            Checkbox::make('generate_api_token')
                                ->label('Create a game API token')
                                ->helperText('Useful for a game client or local integration. It is shown once after saving.'),
                            TextInput::make('api_token_name')
                                ->label('Token name')
                                ->placeholder('My game client')
                                ->required(fn (callable $get): bool => (bool) $get('generate_api_token'))
                                ->maxLength(80)
                                ->visible(fn (callable $get): bool => (bool) $get('generate_api_token')),
                        ]),
                ])->submitAction('Complete setup'),
            ]);
    }

    public function save(): void
    {
        $state = $this->setupForm()->getState();
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

        if (($state['generate_api_token'] ?? false) && DatabaseSchema::hasTable('personal_access_tokens')) {
            $this->newApiToken = $user->createToken($state['api_token_name'])->plainTextToken;
        }

        if ($this->newApiToken === null) {
            $this->redirect(filament()->getUrl());
        }
    }

    public function continueToApp(): void
    {
        $this->redirect(filament()->getUrl());
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
