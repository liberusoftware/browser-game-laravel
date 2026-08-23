<?php

namespace App\Console\Commands;

use App\Services\RankingService;
use Illuminate\Console\Command;

class UpdatePlayerRankings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'players:update-rankings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate scores and update player rankings';

    /**
     * Execute the console command.
     */
    public function handle(RankingService $rankingService): int
    {
        $this->info('Recalculating player scores...');
        $scoresUpdated = $rankingService->recalculateScores();
        $this->info("Updated scores for {$scoresUpdated} players.");

        $this->info('Updating player rankings...');
        $ranksUpdated = $rankingService->updateAllRankings();
        $this->info("Updated rankings for {$ranksUpdated} players.");

        $this->newLine();
        $this->info('✓ Player rankings updated successfully!');

        return Command::SUCCESS;
    }
}
