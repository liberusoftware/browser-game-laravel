<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ModerationAndAnalyticsRecorded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $recordId,
        public readonly string $kind,
        public readonly ?string $actorId,
        public readonly ?string $targetId,
    ) {}
}
