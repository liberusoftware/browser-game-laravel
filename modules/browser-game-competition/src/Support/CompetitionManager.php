<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Competition\Events\CompetitionCollusionFlagged;
use Liberu\BrowserGame\Competition\Events\CompetitionDefined;
use Liberu\BrowserGame\Competition\Events\CompetitionMatchResolved;
use Liberu\BrowserGame\Competition\Events\CompetitionRewardGranted;
use Liberu\BrowserGame\Competition\Models\CompetitionEntry;
use Liberu\BrowserGame\Competition\Models\CompetitionFlag;
use Liberu\BrowserGame\Competition\Models\CompetitionMatch;
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;
use Liberu\BrowserGame\Competition\Models\CompetitionReward;

final class CompetitionManager
{
    public function define(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null): CompetitionRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }

        $record = DB::transaction(fn (): CompetitionRecord => CompetitionRecord::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'data' => $data,
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'status' => 'active',
        ]));
        CompetitionDefined::dispatch((string) $record->getKey());

        return $record;
    }

    public function create(string $name, string $kind = 'pvp', array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): CompetitionRecord
    {
        if (trim($name) === '') {
            throw ValidationException::withMessages(['name' => 'A name is required.']);
        }
        if (! in_array($kind, ['pvp', 'matchmaking', 'season', 'leaderboard'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported competition kind.']);
        }

        return DB::transaction(function () use ($name, $kind, $data, $tenantId, $teamId, $idempotencyKey): CompetitionRecord {
            if ($idempotencyKey !== null && ($existing = CompetitionRecord::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if ((string) $existing->name !== $name || (string) ($existing->kind ?? '') !== $kind || (string) ($existing->tenant_id ?? '') !== (string) ($tenantId ?? '') || (string) ($existing->team_id ?? '') !== (string) ($teamId ?? '')) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another competition.']);
                }

                return $existing;
            }
            $record = CompetitionRecord::query()->create([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'data' => $data,
                'tenant_id' => $tenantId,
                'team_id' => $teamId,
                'kind' => $kind,
                'idempotency_key' => $idempotencyKey,
                'status' => 'open',
            ]);
            CompetitionDefined::dispatch((string) $record->getKey());

            return $record;
        });
    }

    public function createPvp(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): CompetitionRecord
    {
        return $this->create($name, 'pvp', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createMatchmaking(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): CompetitionRecord
    {
        return $this->create($name, 'matchmaking', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createSeason(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): CompetitionRecord
    {
        return $this->create($name, 'season', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function createLeaderboard(string $name, array $data = [], ?string $tenantId = null, ?string $teamId = null, ?string $idempotencyKey = null): CompetitionRecord
    {
        return $this->create($name, 'leaderboard', $data, $tenantId, $teamId, $idempotencyKey);
    }

    public function queue(CompetitionRecord $competition, string $actorId): CompetitionEntry
    {
        if ($competition->status !== 'open' || ($competition->starts_at !== null && $competition->starts_at->isFuture()) || ($competition->ends_at !== null && $competition->ends_at->isPast())) {
            throw ValidationException::withMessages(['competition' => 'Competition is not accepting entries.']);
        }
        if (trim($actorId) === '') {
            throw ValidationException::withMessages(['actor' => 'An actor is required.']);
        }

        return DB::transaction(function () use ($competition, $actorId): CompetitionEntry {
            $competition = CompetitionRecord::query()->whereKey($competition->getKey())->lockForUpdate()->firstOrFail();
            if (! $this->acceptsEntries($competition)) {
                throw ValidationException::withMessages(['competition' => 'Competition is not accepting entries.']);
            }

            return $competition->entries()->firstOrCreate(['actor_id' => $actorId], ['status' => 'queued', 'rating' => 1000, 'wins' => 0, 'losses' => 0, 'points' => 0]);
        });
    }

    public function match(CompetitionRecord $competition, string $playerA, string $playerB, ?string $idempotencyKey = null): CompetitionMatch
    {
        if ($playerA === $playerB || trim($playerA) === '' || trim($playerB) === '') {
            throw ValidationException::withMessages(['players' => 'Distinct players are required.']);
        }

        return DB::transaction(function () use ($competition, $playerA, $playerB, $idempotencyKey): CompetitionMatch {
            $competition = CompetitionRecord::query()->whereKey($competition->getKey())->lockForUpdate()->firstOrFail();
            if ($idempotencyKey !== null && ($existing = CompetitionMatch::query()->where('competition_id', $competition->getKey())->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first())) {
                if (! (($existing->player_a === $playerA && $existing->player_b === $playerB) || ($existing->player_a === $playerB && $existing->player_b === $playerA))) {
                    throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key belongs to another match.']);
                }

                return $existing;
            }
            if (! $this->acceptsEntries($competition)) {
                throw ValidationException::withMessages(['competition' => 'Competition is not accepting entries.']);
            }
            $this->queue($competition, $playerA);
            $this->queue($competition, $playerB);
            $pairExists = $competition->matches()->where(function ($query) use ($playerA, $playerB): void {
                $query->where(function ($nested) use ($playerA, $playerB): void {
                    $nested->where('player_a', $playerA)->where('player_b', $playerB);
                })->orWhere(function ($nested) use ($playerA, $playerB): void {
                    $nested->where('player_a', $playerB)->where('player_b', $playerA);
                });
            })->exists();
            if ($pairExists && ! (bool) data_get($competition->data, 'anti_collusion.allow_rematch', false)) {
                throw ValidationException::withMessages(['players' => 'This player pairing is blocked by anti-collusion rules.']);
            }

            return $competition->matches()->create(['id' => (string) Str::uuid(), 'player_a' => $playerA, 'player_b' => $playerB, 'status' => 'active', 'idempotency_key' => $idempotencyKey]);
        });
    }

    public function resolve(CompetitionMatch $match, string $winnerId, array $evidence = []): CompetitionMatch
    {
        if ($match->status === 'resolved' && $match->winner_id === $winnerId) {
            return $match->fresh();
        }
        if ($match->status !== 'active' || ! in_array($winnerId, [$match->player_a, $match->player_b], true)) {
            throw ValidationException::withMessages(['winner' => 'The match or winner is invalid.']);
        }

        $alreadyResolved = false;
        $updated = DB::transaction(function () use ($match, $winnerId, $evidence, &$alreadyResolved): CompetitionMatch {
            $match = CompetitionMatch::query()->whereKey($match->getKey())->lockForUpdate()->with('competition')->firstOrFail();
            if ($match->status === 'resolved' && $match->winner_id === $winnerId) {
                $alreadyResolved = true;

                return $match;
            }
            $winner = $match->competition->entries()->where('actor_id', $winnerId)->lockForUpdate()->firstOrFail();
            $loserId = $match->player_a === $winnerId ? $match->player_b : $match->player_a;
            $loser = $match->competition->entries()->where('actor_id', $loserId)->lockForUpdate()->firstOrFail();
            $winner->update(['wins' => $winner->wins + 1, 'points' => $winner->points + 3, 'rating' => $winner->rating + 25, 'status' => 'completed']);
            $loser->update(['losses' => $loser->losses + 1, 'rating' => max(0, $loser->rating - 25), 'status' => 'completed']);
            $match->update(['status' => 'resolved', 'winner_id' => $winnerId, 'evidence' => $evidence]);
            foreach ((array) data_get($match->competition->data, 'rewards.winner', []) as $rewardKey => $quantity) {
                $reward = CompetitionReward::query()->firstOrCreate(['competition_id' => $match->competition_id, 'actor_id' => $winnerId, 'reward_key' => (string) $rewardKey], ['quantity' => max(1, (int) $quantity), 'data' => ['match_id' => $match->getKey()]]);
                if ($reward->wasRecentlyCreated) {
                    CompetitionRewardGranted::dispatch((int) $reward->getKey(), $winnerId, (string) $rewardKey);
                }
            }

            return $match->fresh();
        });
        if (! $alreadyResolved) {
            CompetitionMatchResolved::dispatch((string) $updated->getKey(), $winnerId, $updated->player_a === $winnerId ? $updated->player_b : $updated->player_a);
        }

        return $updated;
    }

    public function leaderboard(CompetitionRecord $competition, int $limit = 100): Collection
    {
        return $competition->entries()->orderByDesc('points')->orderByDesc('rating')->limit(min(100, max(1, $limit)))->get();
    }

    public function claimReward(string $actorId, CompetitionReward|int $reward): CompetitionReward
    {
        return DB::transaction(function () use ($actorId, $reward): CompetitionReward {
            $reward = CompetitionReward::query()->whereKey($reward instanceof CompetitionReward ? $reward->getKey() : $reward)->where('actor_id', $actorId)->lockForUpdate()->firstOrFail();
            if ($reward->claimed_at === null) {
                $reward->update(['claimed_at' => now()]);
            }

            return $reward->refresh();
        });
    }

    public function flagCollusion(CompetitionRecord $competition, string $actorId, string $reason, ?CompetitionMatch $match = null): CompetitionFlag
    {
        if (trim($actorId) === '' || trim($reason) === '') {
            throw ValidationException::withMessages(['flag' => 'An actor and reason are required.']);
        }
        if ($match !== null && (string) $match->competition_id !== (string) $competition->getKey()) {
            throw ValidationException::withMessages(['match' => 'The match does not belong to this competition.']);
        }
        $flag = DB::transaction(fn (): CompetitionFlag => CompetitionFlag::query()->create(['competition_id' => $competition->getKey(), 'match_id' => $match?->getKey(), 'actor_id' => $actorId, 'reason' => $reason, 'status' => 'open']));
        CompetitionCollusionFlagged::dispatch((int) $flag->getKey(), (string) $competition->getKey(), $actorId);

        return $flag;
    }

    private function acceptsEntries(CompetitionRecord $competition): bool
    {
        return $competition->status === 'open'
            && ($competition->starts_at === null || ! $competition->starts_at->isFuture())
            && ($competition->ends_at === null || ! $competition->ends_at->isPast());
    }
}
