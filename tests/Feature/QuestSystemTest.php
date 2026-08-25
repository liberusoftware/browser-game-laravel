<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Quest;
use App\Models\Quest;
use App\Services\QuestService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestSystemTest extends TestCase
{
    use RefreshDatabase;

    protected QuestService $questService;

    protected Player $player;

    protected Quest $quest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->questService = new QuestService();

        // Create test player
        $this->player = Player::create([
            'username' => 'testplayer',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'level' => 1,
            'experience' => 0,
        ]);
        Sanctum::actingAs($this->player);

        // Create test item for rewards
        $item = Item::factory()->create([
            'name' => 'Test Sword',
            'description' => 'A test weapon',
            'type' => 'weapon',
            'rarity' => 'common',
        ]);

        // Create test quest
        $this->quest = Quest::create([
            'name' => 'Test Quest',
            'description' => 'A test quest for testing',
            'experience_reward' => 50,
            'item_reward_id' => $item->id,
        ]);
    }

    #[Test]
    public function player_can_accept_a_quest()
    {
        $playerQuest = $this->questService->acceptQuest($this->player, $this->quest);

        $this->assertInstanceOf(Player_Quest::class, $playerQuest);
        $this->assertEquals($this->player->id, $playerQuest->player_id);
        $this->assertEquals($this->quest->id, $playerQuest->quest_id);
        $this->assertEquals('in-progress', $playerQuest->status);
    }

    #[Test]
    public function player_cannot_accept_same_quest_twice()
    {
        $this->questService->acceptQuest($this->player, $this->quest);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Quest already in progress');

        $this->questService->acceptQuest($this->player, $this->quest);
    }

    #[Test]
    public function player_cannot_accept_completed_quest()
    {
        $playerQuest = Player_Quest::create([
            'player_id' => $this->player->id,
            'quest_id' => $this->quest->id,
            'status' => 'completed',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Quest already completed');

        $this->questService->acceptQuest($this->player, $this->quest);
    }

    #[Test]
    public function player_can_complete_quest_and_receive_experience()
    {
        // Accept quest first
        $this->questService->acceptQuest($this->player, $this->quest);

        // Complete quest
        $rewards = $this->questService->completeQuest($this->player, $this->quest);

        // Refresh player from database
        $this->player->refresh();

        $this->assertEquals(50, $this->player->experience);
        $this->assertArrayHasKey('experience', $rewards);
        $this->assertEquals(50, $rewards['experience']);

        // Verify quest status is updated
        $playerQuest = Player_Quest::where('player_id', $this->player->id)
            ->where('quest_id', $this->quest->id)
            ->first();
        $this->assertEquals('completed', $playerQuest->status);
    }

    #[Test]
    public function player_can_complete_quest_and_receive_item_reward()
    {
        // Accept quest first
        $this->questService->acceptQuest($this->player, $this->quest);

        // Complete quest
        $rewards = $this->questService->completeQuest($this->player, $this->quest);

        // Verify item was added to player inventory
        $this->assertArrayHasKey('item', $rewards);
        $this->assertEquals('Test Sword', $rewards['item']->name);
    }

    #[Test]
    public function player_levels_up_when_earning_enough_experience()
    {
        // Create quest with enough XP to level up
        $bigQuest = Quest::create([
            'name' => 'Big Quest',
            'description' => 'A quest with lots of XP',
            'experience_reward' => 150,
            'item_reward_id' => null,
        ]);

        // Accept and complete quest
        $this->questService->acceptQuest($this->player, $bigQuest);
        $rewards = $this->questService->completeQuest($this->player, $bigQuest);

        // Refresh player
        $this->player->refresh();

        // Player should have leveled up (level 1 requires 100 XP, so 150 XP should level up)
        $this->assertEquals(2, $this->player->level);
        $this->assertEquals(150, $this->player->experience);
        $this->assertArrayHasKey('level_up', $rewards);
        $this->assertTrue($rewards['level_up']);
    }

    #[Test]
    public function player_cannot_complete_quest_not_in_progress()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Quest not found or not in progress');

        $this->questService->completeQuest($this->player, $this->quest);
    }

    #[Test]
    public function can_get_available_quests()
    {
        // Create additional quests
        $quest2 = Quest::create([
            'name' => 'Another Quest',
            'description' => 'Another test quest',
            'experience_reward' => 25,
            'item_reward_id' => null,
        ]);

        // Accept one quest
        $this->questService->acceptQuest($this->player, $this->quest);

        // Get available quests
        $availableQuests = $this->questService->getAvailableQuests($this->player);

        // Should only show quest2
        $this->assertCount(1, $availableQuests);
        $this->assertEquals('Another Quest', $availableQuests->first()->name);
    }

    #[Test]
    public function can_get_active_quests()
    {
        // Accept quest
        $this->questService->acceptQuest($this->player, $this->quest);

        // Get active quests
        $activeQuests = $this->questService->getActiveQuests($this->player);

        $this->assertCount(1, $activeQuests);
        $this->assertEquals('Test Quest', $activeQuests->first()->name);
    }

    #[Test]
    public function can_get_completed_quests()
    {
        // Accept and complete quest
        $this->questService->acceptQuest($this->player, $this->quest);
        $this->questService->completeQuest($this->player, $this->quest);

        // Get completed quests
        $completedQuests = $this->questService->getCompletedQuests($this->player);

        $this->assertCount(1, $completedQuests);
        $this->assertEquals('Test Quest', $completedQuests->first()->name);
    }

    #[Test]
    public function player_can_abandon_quest()
    {
        // Accept quest
        $this->questService->acceptQuest($this->player, $this->quest);

        // Abandon quest
        $result = $this->questService->abandonQuest($this->player, $this->quest);

        $this->assertTrue($result);

        // Verify quest is removed
        $playerQuest = Player_Quest::where('player_id', $this->player->id)
            ->where('quest_id', $this->quest->id)
            ->first();
        $this->assertNull($playerQuest);
    }

    #[Test]
    public function player_cannot_abandon_quest_not_in_progress()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Quest not found or not in progress');

        $this->questService->abandonQuest($this->player, $this->quest);
    }

    #[Test]
    public function quest_api_returns_available_quests()
    {
        $response = $this->getJson('/api/quests/available?player_id='.$this->player->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'description', 'experience_reward'],
                ],
            ]);
    }

    #[Test]
    public function quest_api_can_accept_quest()
    {
        $response = $this->postJson('/api/quests/'.$this->quest->id.'/accept', [
            'player_id' => $this->player->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Quest accepted successfully',
            ]);
    }

    #[Test]
    public function quest_api_can_complete_quest()
    {
        // Accept quest first
        $this->questService->acceptQuest($this->player, $this->quest);

        $response = $this->postJson('/api/quests/'.$this->quest->id.'/complete', [
            'player_id' => $this->player->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Quest completed successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'quest',
                    'rewards',
                ],
            ]);
    }

    #[Test]
    public function quest_api_can_abandon_quest()
    {
        // Accept quest first
        $this->questService->acceptQuest($this->player, $this->quest);

        $response = $this->deleteJson('/api/quests/'.$this->quest->id.'/abandon', [
            'player_id' => $this->player->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Quest abandoned successfully',
            ]);
    }

    #[Test]
    public function quest_relationships_work_correctly()
    {
        // Test Player -> Quest relationship
        $this->questService->acceptQuest($this->player, $this->quest);

        $playerQuests = $this->player->quests;
        $this->assertCount(1, $playerQuests);
        $this->assertEquals('Test Quest', $playerQuests->first()->name);

        // Test Quest -> Item relationship
        $this->assertNotNull($this->quest->itemReward);
        $this->assertEquals('Test Sword', $this->quest->itemReward->name);
    }
}
