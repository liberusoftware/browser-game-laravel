<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\QuestsLivewire\Livewire;

use Liberu\BrowserGame\Quests\Queries\QuestQuery;
use Livewire\Component;

final class QuestCatalog extends Component
{
    public function render(): mixed
    {
        $quests = app(QuestQuery::class)->visible(null, null)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-quests-livewire::quest-catalog', ['quests' => $quests]);
    }
}
