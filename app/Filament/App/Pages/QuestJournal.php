<?php

namespace App\Filament\App\Pages;

use App\Models\Player;
use App\Models\Quest;
use App\Services\QuestService;
use App\Services\StarterAdventureService;
use App\Settings\GameSettings;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;

class QuestJournal extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup = 'Play & progress';

    protected static ?int $navigationSort = -10;

    protected string $view = 'filament.app.pages.quest-journal';

    public function accept(int $questId): void
    {
        abort_unless(auth()->check(), 403);
        $quest = Quest::findOrFail($questId);
        $accepted = DB::transaction(function () use ($quest): bool {
            $player = Player::where('email', auth()->user()->email)->lockForUpdate()->first();
            abort_unless($player, 403);

            if (! app(StarterAdventureService::class)->prerequisiteCompleted($player, $quest)) {
                throw ValidationException::withMessages(['adventure' => 'Complete the previous chapter before accepting this quest.']);
            }

            if ($player->quests()->whereKey($quest->id)->exists()) {
                return false;
            }

            if ($player->activeQuests()->count() >= app(GameSettings::class)->max_active_quests) {
                return false;
            }

            app(QuestService::class)->acceptQuest($player, $quest);

            return true;
        });

        Notification::make()->title($accepted ? 'Quest accepted — your next chapter awaits' : 'Quest already accepted or active quest limit reached')->color($accepted ? 'success' : 'warning')->send();
    }

    public function completeAdventure(int $questId, string $choice): void
    {
        abort_unless(auth()->check(), 403);
        $player = auth()->user()->player;
        abort_unless($player, 403);
        $quest = Quest::findOrFail($questId);
        $rewards = app(StarterAdventureService::class)->complete($player, $quest, $choice);
        $this->resetErrorBag('adventure');
        Notification::make()->title('Chapter complete!')
            ->body(($rewards['experience'] ?? 0).' XP earned. Your reward is in your inventory.'.(! empty($rewards['level_up']) ? ' You leveled up!' : ''))
            ->success()->send();
    }

    protected function getViewData(): array
    {
        $player = auth()->user()?->player;
        $quests = Quest::with('itemReward')->orderBy('experience_reward')->paginate(12);
        $statuses = $player?->quests()->whereIn('quests.id', $quests->pluck('id'))->pluck('player__quests.status', 'quests.id') ?? collect();

        $completedStarterKeys = $player?->completedQuests()->whereNotNull('starter_key')->pluck('starter_key')->all() ?? [];

        return compact('player', 'quests', 'statuses', 'completedStarterKeys');
    }
}
