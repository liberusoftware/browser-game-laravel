<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Services\RankingService;
use Illuminate\Database\Seeder;

class PlayerRankingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample players with varying levels and experience
        $players = [
            ['username' => 'DragonSlayer', 'email' => 'dragon@example.com', 'level' => 50, 'experience' => 25000],
            ['username' => 'MasterMage', 'email' => 'mage@example.com', 'level' => 45, 'experience' => 22500],
            ['username' => 'ShadowNinja', 'email' => 'ninja@example.com', 'level' => 40, 'experience' => 20000],
            ['username' => 'HolyKnight', 'email' => 'knight@example.com', 'level' => 35, 'experience' => 17500],
            ['username' => 'ElvenArcher', 'email' => 'archer@example.com', 'level' => 30, 'experience' => 15000],
            ['username' => 'DwarfWarrior', 'email' => 'dwarf@example.com', 'level' => 25, 'experience' => 12500],
            ['username' => 'WiseWizard', 'email' => 'wizard@example.com', 'level' => 20, 'experience' => 10000],
            ['username' => 'RogueThief', 'email' => 'rogue@example.com', 'level' => 15, 'experience' => 7500],
            ['username' => 'BraveBard', 'email' => 'bard@example.com', 'level' => 10, 'experience' => 5000],
            ['username' => 'Apprentice', 'email' => 'apprentice@example.com', 'level' => 5, 'experience' => 2500],
        ];

        foreach ($players as $playerData) {
            Player::factory()->create([
                'username' => $playerData['username'],
                'email' => $playerData['email'],
                'level' => $playerData['level'],
                'experience' => $playerData['experience'],
                'password' => bcrypt('password'),
            ]);
        }

        // Calculate scores and rankings
        $rankingService = new RankingService();
        $rankingService->recalculateScores();
        $rankingService->updateAllRankings();

        $this->command->info('Created 10 sample players with rankings!');
    }
}
