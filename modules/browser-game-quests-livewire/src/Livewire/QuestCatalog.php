<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsLivewire\Livewire;

use Liberu\BrowserGame\Quests\Queries\QuestQuery;
use Liberu\BrowserGame\Quests\Support\QuestsManager;
use Livewire\Component;

final class QuestCatalog extends Component
{
    public ?string $message = null;

    public function accept(string $questId): void
    {
        abort_unless(auth()->check(), 403);
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $quest = app(QuestQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($questId)->where('status', 'active')->firstOrFail();
        app(QuestsManager::class)->accept($quest, (string) auth()->id(), [], 'livewire:accept:'.auth()->id().':'.$questId);
        $this->message = 'Quest accepted.';
    }

    public function complete(string $questId): void
    {
        abort_unless(auth()->check(), 403);
        $quest = $this->visibleQuest($questId);
        app(QuestsManager::class)->complete($quest, (string) auth()->id(), 'livewire:complete:'.auth()->id().':'.$questId);
        $this->message = 'Quest completed.';
    }

    public function abandon(string $questId): void
    {
        abort_unless(auth()->check(), 403);
        $quest = $this->visibleQuest($questId);
        app(QuestsManager::class)->abandon($quest, (string) auth()->id());
        $this->message = 'Quest abandoned.';
    }

    public function render(): mixed
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;
        $quests = app(QuestQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-quests-livewire::quest-catalog', ['quests' => $quests]);
    }

    private function visibleQuest(string $questId): mixed
    {
        $user = auth()->user();
        $team = is_object($user) && method_exists($user, 'currentTeam') ? $user->currentTeam : null;

        return app(QuestQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($questId)->firstOrFail();
    }
}
