<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlayerRankingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_calculates_score_when_player_is_created()
    {
        $player = Player::factory()->create([
            'level' => 10,
            'experience' => 500,
        ]);

        $expectedScore = (10 * 100) + 500; // 1500
        $this->assertEquals($expectedScore, $player->calculateScore());
    }

    #[Test]
    public function it_updates_player_score_when_level_changes()
    {
        $player = Player::factory()->create([
            'level' => 5,
            'experience' => 100,
            'score' => 600,
        ]);

        $player->level = 10;
        $player->save();

        $rankingService = new RankingService();
        $rankingService->updatePlayerRanking($player);

        $player->refresh();
        $expectedScore = (10 * 100) + 100; // 1100
        $this->assertEquals($expectedScore, $player->score);
    }

    #[Test]
    public function it_maintains_ranking_order_after_updates()
    {
        // Create players with initial scores
        $player1 = Player::factory()->create(['level' => 5, 'experience' => 100]);
        $player2 = Player::factory()->create(['level' => 10, 'experience' => 500]);
        $player3 = Player::factory()->create(['level' => 8, 'experience' => 300]);

        $rankingService = new RankingService();
        $rankingService->recalculateScores();
        $rankingService->updateAllRankings();

        $player1->refresh();
        $player2->refresh();
        $player3->refresh();

        // Verify initial rankings: player2 > player3 > player1
        $this->assertEquals(1, $player2->rank);
        $this->assertEquals(2, $player3->rank);
        $this->assertEquals(3, $player1->rank);

        // Update player1 to have highest score
        $player1->level = 20;
        $player1->experience = 1000;
        $player1->save();
        $rankingService->updatePlayerRanking($player1);

        $player1->refresh();
        $player2->refresh();
        $player3->refresh();

        // Verify updated rankings: player1 > player2 > player3
        $this->assertEquals(1, $player1->rank);
        $this->assertTrue($player1->rank < $player2->rank);
    }

    #[Test]
    public function it_handles_multiple_players_with_same_score()
    {
        // Create players with identical scores but different levels
        $player1 = Player::factory()->create(['level' => 10, 'experience' => 100, 'score' => 1100]);
        $player2 = Player::factory()->create(['level' => 11, 'experience' => 0, 'score' => 1100]);

        $rankingService = new RankingService();
        $rankingService->updateAllRankings();

        $player1->refresh();
        $player2->refresh();

        // Both should have ranks, and they should be different
        $this->assertNotNull($player1->rank);
        $this->assertNotNull($player2->rank);
        $this->assertNotEquals($player1->rank, $player2->rank);
    }

    #[Test]
    public function player_model_has_ranking_attributes()
    {
        $player = Player::factory()->create([
            'level' => 5,
            'experience' => 100,
        ]);

        $player->refresh();

        $this->assertArrayHasKey('rank', $player->getAttributes());
        $this->assertArrayHasKey('score', $player->getAttributes());
        $this->assertArrayHasKey('last_rank_update', $player->getAttributes());
    }

    #[Test]
    public function ranking_service_can_get_top_players()
    {
        // Create multiple players
        for ($i = 1; $i <= 20; $i++) {
            Player::factory()->create([
                'level' => $i,
                'experience' => $i * 50,
                'rank' => 21 - $i, // Reverse order
            ]);
        }

        $rankingService = new RankingService();
        $topPlayers = $rankingService->getTopPlayers(5);

        $this->assertCount(5, $topPlayers);
        foreach ($topPlayers as $index => $player) {
            $this->assertEquals($index + 1, $player->rank);
        }
    }
}
