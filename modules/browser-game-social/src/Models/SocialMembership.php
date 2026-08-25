<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SocialMembership extends Model
{
    protected $table = 'browser_game_social_memberships';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    public function social(): BelongsTo
    {
        return $this->belongsTo(SocialRecord::class, 'social_id');
    }
}
