<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\LiveOpsLivewire\Livewire;

use Liberu\BrowserGame\LiveOps\Queries\LiveOpsQuery;
use Liberu\BrowserGame\LiveOps\Support\LiveOpsManager;
use Livewire\Component;

final class LiveOpsCatalog extends Component
{
    public ?string $message = null;

    public function claim(string $recordId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $record = app(LiveOpsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($recordId)->where('status', 'published')->firstOrFail();
        app(LiveOpsManager::class)->claim((string) auth()->id(), $record);
        $this->message = 'Claim recorded.';
    }

    public function claimDaily(string $recordId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $record = app(LiveOpsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($recordId)->where('status', 'published')->firstOrFail();
        app(LiveOpsManager::class)->claimDaily((string) auth()->id(), $record);
        $this->message = 'Daily reward claimed.';
    }

    public function status(string $recordId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $record = app(LiveOpsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($recordId)->where('kind', 'daily_activity')->firstOrFail();
        $status = app(LiveOpsManager::class)->dailyStatus((string) auth()->id(), $record);
        $this->message = $status['claimed'] ? 'Daily reward already claimed (streak '.$status['streak'].').' : 'Daily reward available (streak '.$status['streak'].').';
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $liveOps = app(LiveOpsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'published')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-live-ops-livewire::live-ops-catalog', ['liveOps' => $liveOps]);
    }
}
