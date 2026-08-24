<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsLivewire\Livewire;

use Liberu\BrowserGame\LiveOps\Models\LiveOpsRecord;
use Liberu\BrowserGame\LiveOps\Queries\LiveOpsQuery;
use Liberu\BrowserGame\LiveOps\Support\LiveOpsManager;
use Livewire\Component;

final class LiveOpsCatalog extends Component
{
    public ?string $message = null;

    public function claim(string $recordId): void
    {
        abort_unless(auth()->check(), 403);
        $record = LiveOpsRecord::query()->findOrFail($recordId);
        app(LiveOpsManager::class)->claim((string) auth()->id(), $record);
        $this->message = 'Claim recorded.';
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $liveOps = app(LiveOpsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'published')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-live-ops-livewire::live-ops-catalog', ['liveOps' => $liveOps]);
    }
}
