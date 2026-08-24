<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Social\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SocialRecord extends Model
{
    use HasUuids;

    protected $table = 'browser_game_social';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SocialMembership::class, 'social_id');
    }
}
