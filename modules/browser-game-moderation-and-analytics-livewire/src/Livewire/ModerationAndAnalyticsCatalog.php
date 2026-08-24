<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsLivewire\Livewire;

use Liberu\BrowserGame\ModerationAndAnalytics\Queries\ModerationAndAnalyticsQuery;
use Livewire\Component;

final class ModerationAndAnalyticsCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $moderationAndAnalytics = app(ModerationAndAnalyticsQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-moderation-and-analytics-livewire::moderation-and-analytics-catalog', ['moderation-and-analytics' => $moderationAndAnalytics]);
    }
}
