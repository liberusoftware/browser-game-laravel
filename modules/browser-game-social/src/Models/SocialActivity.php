<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Models;

use Illuminate\Database\Eloquent\Model;

final class SocialActivity extends Model
{
    protected $table = 'browser_game_social_activity';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
