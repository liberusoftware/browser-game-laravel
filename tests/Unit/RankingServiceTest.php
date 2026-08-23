<?php

namespace Tests\Unit;

use App\Models\Player;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RankingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RankingService $rankingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rankingService = new RankingService();
    }

    #[Test]
    public function it_calculates_player_score_correctly()
    {
        $player = Player::factory()->create([
            'level' => 5,
            'experience' => 250,
        ]);

        $expectedScore = (5 * 100) + 250; // 750
        $this->assertEquals($expectedScore, $player->calculateScore());
    }

    #[Test]
    public function it_recalculates_scores_for_all_players()
    {
        // Create players with different levels and experience
        Player::factory()->create(['level' => 10, 'experience' => 500, 'score' => 0]);
        Player::factory()->create(['level' => 5, 'experience' => 200, 'score' => 0]);
        Player::factory()->create(['level' => 15, 'experience' => 1000, 'score' => 0]);

        $updatedCount = $this->rankingService->recalculateScores();

        $this->assertEquals(3, $updatedCount);

        $players = Player::all();
        foreach ($players as $player) {
            $expectedScore = ($player->level * 100) + $player->experience;
            $this->assertEquals($expectedScore, $player->score);
        }
    }

    #[Test]
    public function it_updates_rankings_correctly()
    {
        // Create players with pre-calculated scores
        $player1 = Player::factory()->create(['level' => 15, 'experience' => 1000, 'score' => 2500]);
        $player2 = Player::factory()->create(['level' => 10, 'experience' => 500, 'score' => 1500]);
        $player3 = Player::factory()->create(['level' => 5, 'experience' => 200, 'score' => 700]);

        $this->rankingService->updateAllRankings();

        $player1->refresh();
        $player2->refresh();
        $player3->refresh();

        $this->assertEquals(1, $player1->rank);
        $this->assertEquals(2, $player2->rank);
        $this->assertEquals(3, $player3->rank);
        $this->assertNotNull($player1->last_rank_update);
    }

    #[Test]
    public function it_ranks_players_by_score_then_level_then_experience()
    {
        // Players with same score but different levels
        $player1 = Player::factory()->create(['level' => 10, 'experience' => 100, 'score' => 1100]);
        $player2 = Player::factory()->create(['level' => 11, 'experience' => 0, 'score' => 1100]);

        $this->rankingService->updateAllRankings();

        $player1->refresh();
        $player2->refresh();

        // Player2 should rank higher (level 11 > level 10)
        $this->assertEquals(1, $player2->rank);
        $this->assertEquals(2, $player1->rank);
    }

    #[Test]
    public function it_gets_top_players()
    {
        // Create 15 players
        for ($i = 1; $i <= 15; $i++) {
            Player::factory()->create([
                'level' => $i,
                'experience' => $i * 100,
                'score' => ($i * 100) + ($i * 100),
                'rank' => 16 - $i, // Reverse order for testing
            ]);
        }

        $topPlayers = $this->rankingService->getTopPlayers(10);

        $this->assertCount(10, $topPlayers);
        $this->assertEquals(1, $topPlayers->first()->rank);
        $this->assertEquals(10, $topPlayers->last()->rank);
    }

    #[Test]
    public function it_updates_player_ranking()
    {
        $player = Player::factory()->create(['level' => 1, 'experience' => 0, 'score' => 0]);

        // Simulate player leveling up
        $player->level = 10;
        $player->experience = 500;
        $player->save();

        $this->rankingService->updatePlayerRanking($player);

        $player->refresh();
        $expectedScore = (10 * 100) + 500; // 1500
        $this->assertEquals($expectedScore, $player->score);
        $this->assertNotNull($player->rank);
    }

    #[Test]
    public function it_returns_player_rank()
    {
        $player = Player::factory()->create(['rank' => 5]);

        $rank = $this->rankingService->getPlayerRank($player);

        $this->assertEquals(5, $rank);
    }

    #[Test]
    public function it_handles_players_with_no_rank()
    {
        $player = Player::factory()->create(['rank' => null]);

        $rank = $this->rankingService->getPlayerRank($player);

        $this->assertNull($rank);
    }
}
