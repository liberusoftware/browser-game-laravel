<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialLivewire\Livewire;

use Liberu\BrowserGame\Social\Queries\SocialQuery;
use Livewire\Component;

final class SocialCatalog extends Component
{
    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $social = app(SocialQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-social-livewire::social-catalog', ['social' => $social]);
    }
}
