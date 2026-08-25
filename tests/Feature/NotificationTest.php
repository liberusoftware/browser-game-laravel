<?php

namespace Tests\Feature;

use App\Events\AchievementUnlocked;
use App\Events\PlayerLeveledUp;
use App\Events\QuestCompleted;
use App\Models\GameNotification;
use App\Models\Player;
use App\Models\Quest;
use App\Notifications\AchievementUnlockedNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quest_completed_event_creates_notification()
    {
        Notification::fake();
        Event::fake();

        $player = Player::factory()->create();
        $quest = Quest::factory()->create(['name' => 'Epic Quest']);

        event(new QuestCompleted($player, $quest, 100));

        Event::assertDispatched(QuestCompleted::class, function ($event) use ($player, $quest) {
            return $event->player->id === $player->id &&
                   $event->quest->id === $quest->id;
        });
    }

    public function test_level_up_event_is_dispatched()
    {
        Event::fake();

        $player = Player::factory()->create(['level' => 5]);

        event(new PlayerLeveledUp($player, 6, 5));

        Event::assertDispatched(PlayerLeveledUp::class, function ($event) use ($player) {
            return $event->player->id === $player->id &&
                   $event->newLevel === 6 &&
                   $event->oldLevel === 5;
        });
    }

    public function test_achievement_unlocked_notification_can_be_sent()
    {
        Notification::fake();

        $player = Player::factory()->create();

        $player->notify(new AchievementUnlockedNotification('First Victory', 'Won your first battle'));

        Notification::assertSentTo($player, AchievementUnlockedNotification::class);
    }

    public function test_notification_service_can_create_quest_notification()
    {
        Event::fake();

        $service = new NotificationService();
        $player = Player::factory()->create();
        $quest = Quest::factory()->create(['name' => 'Dragon Slayer']);

        $service->notifyQuestCompleted($player, $quest, 100);

        Event::assertDispatched(QuestCompleted::class);
    }

    public function test_notification_service_can_create_level_up_notification()
    {
        Event::fake();

        $service = new NotificationService();
        $player = Player::factory()->create(['level' => 10]);

        $service->notifyLevelUp($player, 11, 10);

        Event::assertDispatched(PlayerLeveledUp::class);
    }

    public function test_notification_service_can_create_achievement_notification()
    {
        Event::fake();

        $service = new NotificationService();
        $player = Player::factory()->create();

        $service->notifyAchievementUnlocked($player, 'Master Trader', 'Completed 100 trades');

        Event::assertDispatched(AchievementUnlocked::class);
    }

    public function test_game_notification_can_be_created()
    {
        $player = Player::factory()->create();

        $notification = GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'This is a test message',
            'data' => ['key' => 'value'],
        ]);

        $this->assertDatabaseHas('game_notifications', [
            'player_id' => $player->id,
            'type' => 'test',
            'title' => 'Test Notification',
            'is_read' => false,
        ]);
    }

    public function test_game_notification_can_be_marked_as_read()
    {
        $player = Player::factory()->create();

        $notification = GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'This is a test message',
        ]);

        $this->assertFalse($notification->is_read);

        $notification->markAsRead();

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_player_can_get_unread_notifications()
    {
        $player = Player::factory()->create();

        GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test1',
            'title' => 'Unread Notification 1',
            'message' => 'Message 1',
        ]);

        GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test2',
            'title' => 'Unread Notification 2',
            'message' => 'Message 2',
        ]);

        $readNotification = GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test3',
            'title' => 'Read Notification',
            'message' => 'Message 3',
            'is_read' => true,
        ]);

        $unreadCount = $player->unreadNotifications()->count();

        $this->assertEquals(2, $unreadCount);
    }

    public function test_notification_service_can_get_unread_count()
    {
        $service = new NotificationService();
        $player = Player::factory()->create();

        GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test1',
            'title' => 'Notification 1',
            'message' => 'Message 1',
        ]);

        GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test2',
            'title' => 'Notification 2',
            'message' => 'Message 2',
        ]);

        $count = $service->getUnreadCount($player);

        $this->assertEquals(2, $count);
    }

    public function test_notification_service_can_mark_all_as_read()
    {
        $service = new NotificationService();
        $player = Player::factory()->create();

        GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test1',
            'title' => 'Notification 1',
            'message' => 'Message 1',
        ]);

        GameNotification::create([
            'player_id' => $player->id,
            'type' => 'test2',
            'title' => 'Notification 2',
            'message' => 'Message 2',
        ]);

        $this->assertEquals(2, $service->getUnreadCount($player));

        $service->markAllAsRead($player);

        $this->assertEquals(0, $service->getUnreadCount($player));
    }
}
