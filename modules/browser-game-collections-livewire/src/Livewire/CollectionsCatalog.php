<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsLivewire\Livewire;

use Liberu\BrowserGame\Collections\Models\CollectionsRecord;
use Liberu\BrowserGame\Collections\Queries\CollectionsQuery;
use Liberu\BrowserGame\Collections\Support\CollectionsManager;
use Livewire\Component;

final class CollectionsCatalog extends Component
{
    public ?string $message = null;

    public function record(string $collectionId, string $entryKey): void
    {
        abort_unless(auth()->check(), 403);
        $collection = CollectionsRecord::query()->findOrFail($collectionId);
        app(CollectionsManager::class)->record((string) auth()->id(), $collection, $entryKey, 1, 'livewire:'.auth()->id().':'.$collectionId.':'.$entryKey.':'.now()->toDateString());
        $this->message = 'Collection progress recorded.';
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $collections = app(CollectionsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->with('entries')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-collections-livewire::collections-catalog', ['collections' => $collections]);
    }
}
