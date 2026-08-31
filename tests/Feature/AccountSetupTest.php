<?php

use App\Filament\App\Pages\AccountSetup;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Liberu\Foundation\Organizations\Models\Team;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects a newly registered account to setup', function (): void {
    $user = User::factory()->create(['onboarding_completed_at' => null]);
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('filament.app.pages.account-setup'));
});

it('saves profile and team settings and can issue a one-time api token', function (): void {
    $user = User::factory()->create(['onboarding_completed_at' => null]);
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));

    Livewire::test(AccountSetup::class)
        ->set('data', [
            'name' => 'Ari Player',
            'team_name' => 'Ari Games',
            'generate_api_token' => true,
            'api_token_name' => 'Test client',
        ])
        ->call('save')
        ->assertSet('newApiToken', fn (?string $token): bool => filled($token));

    expect($user->fresh()->name)->toBe('Ari Player')
        ->and($user->fresh()->hasCompletedOnboarding())->toBeTrue()
        ->and($team->fresh()->name)->toBe('Ari Games')
        ->and(PersonalAccessToken::query()->where('tokenable_id', $user->id)->count())->toBe(1);
});
