<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;

class TeamManagementService
{
    /**
     * Assign user to the default team, or create a personal team if none exists.
     */
    public function assignUserToDefaultTeam(User $user): void
    {
        $team = Team::first();

        if (! $team) {
            $this->createPersonalTeamForUser($user);

            return;
        }

        if (! $team->users()->where('user_id', $user->id)->exists()) {
            $team->users()->attach($user);
        }

        $user->switchTeam($team);
    }

    /**
     * Create a personal team for the user.
     */
    public function createPersonalTeamForUser(User $user): Team
    {
        $team = $user->ownedTeams()->create([
            'name' => $user->name."'s Team",
            'personal_team' => true,
        ]);

        $user->switchTeam($team);

        return $team;
    }
}
