<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsLivewire\Livewire;

use Liberu\BrowserGame\ModerationAndAnalytics\Models\ModerationAndAnalyticsRecord;
use Liberu\BrowserGame\ModerationAndAnalytics\Queries\ModerationAndAnalyticsQuery;
use Liberu\BrowserGame\ModerationAndAnalytics\Support\ModerationAndAnalyticsManager;
use Livewire\Component;

final class ModerationAndAnalyticsCatalog extends Component
{
    public ?string $message = null;

    public function resolve(string $recordId): void
    {
        abort_unless(auth()->check(), 403);
        app(ModerationAndAnalyticsManager::class)->resolve(ModerationAndAnalyticsRecord::query()->findOrFail($recordId));
        $this->message = 'Record resolved.';
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $moderationAndAnalytics = app(ModerationAndAnalyticsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->whereIn('status', ['active', 'open', 'recorded'])->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-moderation-and-analytics-livewire::moderation-and-analytics-catalog', ['moderationAndAnalytics' => $moderationAndAnalytics]);
    }
}
