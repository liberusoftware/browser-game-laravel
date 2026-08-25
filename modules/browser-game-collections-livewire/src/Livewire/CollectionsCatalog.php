<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CollectionsLivewire\Livewire;

use Liberu\BrowserGame\Collections\Models\CollectionProgress;
use Liberu\BrowserGame\Collections\Queries\CollectionsQuery;
use Liberu\BrowserGame\Collections\Support\CollectionsManager;
use Livewire\Component;

final class CollectionsCatalog extends Component
{
    public ?string $message = null;

    public function record(string $collectionId, string $entryKey): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $collection = app(CollectionsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())
            ->whereKey($collectionId)
            ->where('status', 'active')
            ->firstOrFail();
        app(CollectionsManager::class)->record((string) auth()->id(), $collection, $entryKey, 1, 'livewire:'.auth()->id().':'.$collectionId.':'.$entryKey.':'.now()->toDateString());
        $this->message = 'Collection progress recorded.';
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $collections = app(CollectionsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->with('entries')->latest()->limit(25)->get();
        $progress = auth()->check()
            ? CollectionProgress::query()->where('actor_id', (string) auth()->id())->whereIn('collection_id', $collections->modelKeys())->get()->keyBy(fn (CollectionProgress $item): string => $item->collection_id.':'.$item->entry_key)
            : collect();

        return resolve('view')->make('browser-game-collections-livewire::collections-catalog', ['collections' => $collections, 'progress' => $progress]);
    }
}
