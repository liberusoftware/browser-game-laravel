<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalyticsLivewire\Livewire;

use Liberu\BrowserGame\ModerationAndAnalytics\Queries\ModerationAndAnalyticsQuery;
use Liberu\BrowserGame\ModerationAndAnalytics\Support\ModerationAndAnalyticsManager;
use Livewire\Component;

final class ModerationAndAnalyticsCatalog extends Component
{
    public ?string $message = null;

    public function resolve(string $recordId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $record = app(ModerationAndAnalyticsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())
            ->whereKey($recordId)
            ->firstOrFail();
        app(ModerationAndAnalyticsManager::class)->resolve($record);
        $this->message = 'Record resolved.';
    }

    public function submitReport(string $targetId, string $reason): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        app(ModerationAndAnalyticsManager::class)->record('report', 'User report', (string) auth()->id(), $targetId, ['reason' => $reason], $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), 'livewire:report:'.auth()->id().':'.$targetId.':'.sha1($reason));
        $this->message = 'Report submitted.';
    }

    public function submitAppeal(string $targetId, string $reason): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        app(ModerationAndAnalyticsManager::class)->submitAppeal((string) auth()->id(), $targetId, $reason, [], $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey(), 'livewire:appeal:'.auth()->id().':'.$targetId.':'.sha1($reason));
        $this->message = 'Appeal submitted.';
    }

    public function recordTelemetry(string $name, array $data = []): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        app(ModerationAndAnalyticsManager::class)->recordTelemetry((string) auth()->id(), $name, $data, $team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $this->message = 'Telemetry recorded.';
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $moderationAndAnalytics = app(ModerationAndAnalyticsQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereIn('status', ['active', 'open', 'recorded'])->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-moderation-and-analytics-livewire::moderation-and-analytics-catalog', ['moderationAndAnalytics' => $moderationAndAnalytics]);
    }
}
