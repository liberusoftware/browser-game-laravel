<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CompetitionLivewire\Livewire;

use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Competition\Models\CompetitionMatch;
use Liberu\BrowserGame\Competition\Models\CompetitionReward;
use Liberu\BrowserGame\Competition\Queries\CompetitionQuery;
use Liberu\BrowserGame\Competition\Support\CompetitionManager;
use Livewire\Component;

final class CompetitionCatalog extends Component
{
    public string $error = '';

    public array $leaderboard = [];

    public function queue(string $competitionId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $competition = app(CompetitionQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($competitionId)->firstOrFail();

        try {
            app(CompetitionManager::class)->queue($competition, (string) auth()->id());
            $this->error = '';
            $this->dispatch('browser-game-competition-queued', competitionId: $competitionId);
        } catch (ValidationException $exception) {
            $this->error = (string) collect($exception->errors())->flatten()->first();
        }
    }

    public function match(string $competitionId, string $opponentId): void
    {
        abort_unless(auth()->check(), 403);
        $competition = $this->visibleCompetition($competitionId);

        try {
            app(CompetitionManager::class)->match($competition, (string) auth()->id(), $opponentId, 'livewire:match:'.$competition->getKey().':'.auth()->id().':'.$opponentId);
            $this->error = '';
        } catch (ValidationException $exception) {
            $this->error = (string) collect($exception->errors())->flatten()->first();
        }
    }

    public function resolve(string $matchId, string $winnerId): void
    {
        abort_unless(auth()->check(), 403);
        $team = auth()->user()?->currentTeam;
        $visible = app(CompetitionQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey());
        $match = CompetitionMatch::query()->whereKey($matchId)->whereIn('competition_id', $visible->select('id'))->firstOrFail();
        app(CompetitionManager::class)->resolve($match, $winnerId);
        $this->error = '';
    }

    public function refreshLeaderboard(string $competitionId): void
    {
        abort_unless(auth()->check(), 403);
        $competition = $this->visibleCompetition($competitionId);
        $this->leaderboard = app(CompetitionManager::class)->leaderboard($competition)->map(fn ($entry): array => ['actor_id' => (string) $entry->actor_id, 'points' => (int) $entry->points, 'rating' => (int) $entry->rating, 'wins' => (int) $entry->wins, 'losses' => (int) $entry->losses])->all();
    }

    public function claimReward(int $rewardId): void
    {
        abort_unless(auth()->check(), 403);
        $reward = CompetitionReward::query()->whereKey($rewardId)->where('actor_id', (string) auth()->id())->firstOrFail();
        app(CompetitionManager::class)->claimReward((string) auth()->id(), $reward);
        $this->error = '';
    }

    public function flag(string $competitionId, string $reason, ?string $matchId = null): void
    {
        abort_unless(auth()->check(), 403);
        $competition = $this->visibleCompetition($competitionId);
        $match = $matchId === null ? null : $competition->matches()->whereKey($matchId)->firstOrFail();
        app(CompetitionManager::class)->flagCollusion($competition, (string) auth()->id(), $reason, $match);
        $this->error = '';
    }

    public function render(): mixed
    {
        $team = auth()->user()?->currentTeam;
        $competition = app(CompetitionQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereIn('status', ['active', 'open'])->latest()->limit(25)->get();

        return resolve('view')->make('browser-game-competition-livewire::competition-catalog', ['competition' => $competition]);
    }

    private function visibleCompetition(string $competitionId): mixed
    {
        $team = auth()->user()?->currentTeam;

        return app(CompetitionQuery::class)->visible($team?->getAttribute('tenant_id'), $team?->getKey() === null ? null : (string) $team->getKey())->whereKey($competitionId)->firstOrFail();
    }
}
