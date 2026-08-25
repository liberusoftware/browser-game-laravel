<?php

namespace Tests\Feature\Livewire;

use App\Livewire\QuestBoard;
use App\Models\Item;
use App\Models\Player;
use App\Models\Quest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_successfully()
    {
        Player::factory()->create();
        Quest::factory()->count(3)->create();

        Livewire::test(QuestBoard::class)
            ->assertStatus(200);
    }

    public function test_player_can_accept_quest()
    {
        $player = Player::factory()->create();
        $quest = Quest::factory()->create(['name' => 'Defeat 10 Goblins']);

        Livewire::test(QuestBoard::class)
            ->call('acceptQuest', $quest->id)
            ->assertDispatched('quest-accepted')
            ->assertSee('accepted');
    }

    public function test_player_can_complete_quest()
    {
        $player = Player::factory()->create(['experience' => 50]);
        $quest = Quest::factory()->create(['experience_reward' => 100]);

        // Attach quest as in-progress
        $player->quests()->attach($quest->id, ['status' => 'in-progress']);

        Livewire::test(QuestBoard::class)
            ->call('completeQuest', $quest->id)
            ->assertDispatched('quest-completed')
            ->assertDispatched('player-updated');
    }

    public function test_player_can_abandon_quest()
    {
        $player = Player::factory()->create();
        $quest = Quest::factory()->create(['name' => 'Collect Herbs']);

        // Attach quest as in-progress
        $player->quests()->attach($quest->id, ['status' => 'in-progress']);

        Livewire::test(QuestBoard::class)
            ->call('abandonQuest', $quest->id)
            ->assertDispatched('quest-abandoned')
            ->assertSee('abandoned');
    }

    public function test_quest_completion_awards_experience()
    {
        $player = Player::factory()->create([
            'level' => 1,
            'experience' => 0,
        ]);

        $quest = Quest::factory()->create(['experience_reward' => 50]);
        $player->quests()->attach($quest->id, ['status' => 'in-progress']);

        Livewire::test(QuestBoard::class)
            ->call('completeQuest', $quest->id);

        $this->assertEquals(50, $player->fresh()->experience);
    }

    public function test_quest_completion_awards_item()
    {
        $player = Player::factory()->create();
        $item = Item::factory()->create(['name' => 'Magic Sword']);
        $quest = Quest::factory()->create([
            'experience_reward' => 100,
            'item_reward_id' => $item->id,
        ]);

        $player->quests()->attach($quest->id, ['status' => 'in-progress']);

        Livewire::test(QuestBoard::class)
            ->call('completeQuest', $quest->id);

        $this->assertTrue($player->fresh()->items->contains($item));
    }

    public function test_available_quests_displayed()
    {
        $player = Player::factory()->create();
        $quest = Quest::factory()->create(['name' => 'Epic Quest']);

        Livewire::test(QuestBoard::class)
            ->assertSee('Epic Quest')
            ->assertSee('Available Quests');
    }
}
