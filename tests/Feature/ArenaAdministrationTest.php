<?php

use App\Filament\Admin\Resources\NpcResource;
use App\Filament\Admin\Resources\NpcResource\Pages\CreateNpc;
use App\Filament\Admin\Resources\NpcResource\Pages\EditNpc;
use App\Filament\Admin\Resources\NpcResource\Pages\ListNpcs;
use App\Models\Npc;
use App\Models\User;
use Database\Seeders\NpcSeeder;
use Filament\Facades\Filament;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\RolesPermissions\Models\Role;
use Livewire\Livewire;

it('restricts global NPC management to super administrators', function (): void {
    $this->seed(NpcSeeder::class);
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $npc = Npc::first();
    expect(NpcResource::canViewAny())->toBeFalse()->and(NpcResource::canCreate())->toBeFalse()->and(NpcResource::canEdit($npc))->toBeFalse();
    Livewire::test(EditNpc::class, ['record' => $npc->id])->assertForbidden();
});

it('lets a super administrator tune stats and deactivate an encounter without deleting history', function (): void {
    $this->seed(NpcSeeder::class);
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $user->assignRole('super_admin');
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);
    $npc = Npc::first();
    Livewire::test(ListNpcs::class)->assertSee('Practice Sentinel');
    Livewire::test(CreateNpc::class)->fillForm([
        'name' => 'Custom Arena Instructor', 'slug' => 'custom-arena-instructor',
        'description' => 'A custom opponent for the village arena.', 'region' => 'Dawnvale',
        'difficulty' => 'Training', 'behavior' => 'defensive', 'level' => 1, 'is_active' => true,
        'stats' => $npc->stats, 'experience_reward' => 15, 'gold_reward' => 10,
        'loot_item_id' => $npc->loot_item_id,
    ])->call('create')->assertHasNoFormErrors();
    expect(Npc::where('slug', 'custom-arena-instructor')->exists())->toBeTrue();
    Livewire::test(EditNpc::class, ['record' => $npc->id])
        ->fillForm(['name' => 'Village Sparring Master', 'stats' => array_merge($npc->stats, ['health' => 80]), 'is_active' => false])
        ->call('save')->assertHasNoFormErrors();
    expect($npc->fresh()->name)->toBe('Village Sparring Master')->and($npc->fresh()->stats['health'])->toBe(80)->and($npc->fresh()->is_active)->toBeFalse()
        ->and(NpcResource::canDelete($npc))->toBeFalse();
});
