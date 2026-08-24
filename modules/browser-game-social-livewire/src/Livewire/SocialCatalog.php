<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialLivewire\Livewire;

use Liberu\BrowserGame\Social\Models\SocialRecord;
use Liberu\BrowserGame\Social\Queries\SocialQuery;
use Liberu\BrowserGame\Social\Support\SocialManager;
use Livewire\Component;

final class SocialCatalog extends Component
{
    public ?string $message = null;

    public function send(string $socialId, string $body): void
    {
        abort_unless(auth()->check(), 403);
        app(SocialManager::class)->send((string) auth()->id(), SocialRecord::query()->findOrFail($socialId), $body);
        $this->message = 'Message sent.';
    }

    public function render(): mixed
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $social = app(SocialQuery::class)->visible(null, $teamId === null ? null : (string) $teamId)->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-social-livewire::social-catalog', ['social' => $social]);
    }
}
