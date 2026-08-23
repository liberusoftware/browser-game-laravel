<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Database\Eloquent\Collection;

class RankingService
{
    /**
     * Calculate and update rankings for all players.
     *
     * @return int Number of players updated
     */
    public function updateAllRankings(): int
    {
        // Get all players ordered by score (desc), then level (desc), then experience (desc)
        $players = Player::orderByDesc('score')
            ->orderByDesc('level')
            ->orderByDesc('experience')
            ->get();

        $updatedCount = 0;
        $currentRank = 1;

        foreach ($players as $player) {
            $player->rank = $currentRank;
            $player->last_rank_update = now();
            $player->save();

            $currentRank++;
            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Recalculate scores for all players.
     *
     * @return int Number of players updated
     */
    public function recalculateScores(): int
    {
        $players = Player::all();
        $updatedCount = 0;

        foreach ($players as $player) {
            $newScore = $player->calculateScore();

            if ($player->score !== $newScore) {
                $player->score = $newScore;
                $player->save();
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Update score and ranking for a specific player.
     */
    public function updatePlayerRanking(Player $player): void
    {
        // Recalculate the player's score
        $player->score = $player->calculateScore();
        $player->save();

        // Update rankings for all players
        $this->updateAllRankings();
    }

    /**
     * Get top players by rank.
     *
     * @return Collection
     */
    public function getTopPlayers(int $limit = 10)
    {
        return Player::whereNotNull('rank')
            ->orderBy('rank')
            ->limit($limit)
            ->get();
    }

    /**
     * Get player's ranking position.
     */
    public function getPlayerRank(Player $player): ?int
    {
        return $player->rank;
    }
}
