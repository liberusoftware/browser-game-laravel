<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\ModerationAndAnalytics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class ModerationAndAnalyticsRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_moderation_and_analytics';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array', 'value' => 'decimal:6', 'resolved_at' => 'datetime'];
    }
}
