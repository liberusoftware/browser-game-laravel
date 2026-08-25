<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\BrowserGame\Accounts\AccountsServiceProvider;
use Liberu\BrowserGame\Accounts\Support\AccountsManager;
use Liberu\BrowserGame\AccountsLivewire\AccountsLivewireServiceProvider;
use Liberu\BrowserGame\AccountsLivewire\Livewire\AccountsCatalog;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders and completes the account privacy and lifecycle interactions', function (): void {
    $this->app->register(AccountsServiceProvider::class);
    $this->app->register(AccountsLivewireServiceProvider::class);
    $this->artisan('migrate');

    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->getKey()])->save();
    $account = app(AccountsManager::class)->define('Interactive account', teamId: (string) $team->getKey());

    Livewire::actingAs($user)
        ->test(AccountsCatalog::class)
        ->assertSee('Interactive account')
        ->set('birthYear', 2000)
        ->set('region', 'GB')
        ->set('ageVerified', true)
        ->call('updateAgeRegion', (string) $account->getKey())
        ->set('profileVisibility', 'friends')
        ->call('updatePrivacy', (string) $account->getKey())
        ->call('suspend', (string) $account->getKey())
        ->call('reactivate', (string) $account->getKey())
        ->set('banReason', 'Abusive behavior')
        ->call('ban', (string) $account->getKey());

    $banId = $account->fresh()->bans()->latest()->value('id');

    Livewire::actingAs($user)
        ->test(AccountsCatalog::class)
        ->call('liftBan', (string) $account->getKey(), (int) $banId)
        ->call('requestDeletion', (string) $account->getKey())
        ->call('completeDeletion', (string) $account->getKey())
        ->assertSee('Account deletion completed.');

    expect($account->fresh()->status)->toBe('closed');
});
