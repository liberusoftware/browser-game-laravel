<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsLivewire\Livewire;

use Liberu\BrowserGame\Quests\Models\Quest;
use Liberu\BrowserGame\Quests\Queries\QuestQuery;
use Liberu\BrowserGame\Quests\Support\QuestsManager;
use Livewire\Component;

final class QuestCatalog extends Component
{
    public ?string $message = null;

    public function accept(string $questId): void
    {
        abort_unless(auth()->check(), 403);
        app(QuestsManager::class)->accept(Quest::query()->findOrFail($questId), (string) auth()->id(), [], 'livewire:accept:'.auth()->id().':'.$questId);
        $this->message = 'Quest accepted.';
    }

    public function render(): mixed
    {
        $quests = app(QuestQuery::class)->visible(null, null)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-quests-livewire::quest-catalog', ['quests' => $quests]);
    }
}
