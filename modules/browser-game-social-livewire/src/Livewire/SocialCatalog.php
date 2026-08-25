<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\SocialLivewire\Livewire;

use Liberu\BrowserGame\Social\Queries\SocialQuery;
use Liberu\BrowserGame\Social\Support\SocialManager;
use Livewire\Component;

final class SocialCatalog extends Component
{
    public ?string $message = null;

    public function createFriend(string $targetId): void
    {
        abort_unless(auth()->check(), 403);
        app(SocialManager::class)->createFriendRequest((string) auth()->id(), $targetId, tenantId: $this->tenantId(), teamId: $this->teamId());
        $this->message = 'Friend request sent.';
    }

    public function createGroup(string $kind, string $name): void
    {
        abort_unless(auth()->check(), 403);
        $manager = app(SocialManager::class);
        $actorId = (string) auth()->id();
        $record = match ($kind) {
            'party' => $manager->createParty($actorId, $name, tenantId: $this->tenantId(), teamId: $this->teamId()),
            'chat' => $manager->createChat($actorId, $name, tenantId: $this->tenantId(), teamId: $this->teamId()),
            'guild' => $manager->createGuild($actorId, $name, tenantId: $this->tenantId(), teamId: $this->teamId()),
            'alliance' => $manager->createAlliance($actorId, $name, tenantId: $this->tenantId(), teamId: $this->teamId()),
            default => abort(422, 'Unsupported social group.'),
        };
        $this->message = ucfirst($record->kind).' created.';
    }

    public function sendMail(string $recipientId, string $body): void
    {
        abort_unless(auth()->check(), 403);
        app(SocialManager::class)->createMail((string) auth()->id(), $recipientId, $body, tenantId: $this->tenantId(), teamId: $this->teamId());
        $this->message = 'Mail sent.';
    }

    public function join(string $socialId): void
    {
        abort_unless(auth()->check(), 403);
        $social = $this->visibleSocial($socialId)->whereIn('kind', ['party', 'guild', 'alliance', 'chat'])->where('status', 'active')->firstOrFail();
        app(SocialManager::class)->addMember($social, (string) auth()->id());
        $this->message = 'Membership updated.';
    }

    public function respondToFriend(string $socialId, string $status): void
    {
        abort_unless(auth()->check(), 403);
        $social = $this->visibleSocial($socialId)->firstOrFail();
        app(SocialManager::class)->respondToFriendRequest($social, (string) auth()->id(), $status);
        $this->message = 'Friend request updated.';
    }

    public function report(string $targetId, string $reason): void
    {
        abort_unless(auth()->check(), 403);
        app(SocialManager::class)->report((string) auth()->id(), $targetId, $reason, [], $this->tenantId(), $this->teamId(), 'livewire:report:'.auth()->id().':'.$targetId.':'.sha1($reason));
        $this->message = 'Social report submitted.';
    }

    public function updatePermissions(string $socialId, string $memberId, array $permissions): void
    {
        abort_unless(auth()->check(), 403);
        app(SocialManager::class)->updatePermissions($this->visibleSocial($socialId)->firstOrFail(), (string) auth()->id(), $memberId, $permissions);
        $this->message = 'Member permissions updated.';
    }

    public function send(string $socialId, string $body): void
    {
        abort_unless(auth()->check(), 403);
        $social = $this->visibleSocial($socialId)->where('status', 'active')->firstOrFail();
        app(SocialManager::class)->send((string) auth()->id(), $social, $body);
        $this->message = 'Message sent.';
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $social = app(SocialQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->where('status', 'active')->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-social-livewire::social-catalog', ['social' => $social]);
    }

    private function teamId(): ?string
    {
        $teamId = auth()->user()?->currentTeam?->getKey();

        return $teamId === null ? null : (string) $teamId;
    }

    private function tenantId(): ?string
    {
        $tenantId = auth()->user()?->currentTeam?->getAttribute('tenant_id');

        return $tenantId === null ? null : (string) $tenantId;
    }

    private function visibleSocial(string $socialId): mixed
    {
        return app(SocialQuery::class)->visible($this->tenantId(), $this->teamId())->whereKey($socialId);
    }
}
