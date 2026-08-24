<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\Competition\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\BrowserGame\Competition\Events\CompetitionDefined;
use Liberu\BrowserGame\Competition\Models\CompetitionEntry;
use Liberu\BrowserGame\Competition\Models\CompetitionMatch;
use Liberu\BrowserGame\Competition\Models\CompetitionRecord;

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
        if (! in_array($kind, ['pvp', 'matchmaking', 'season', 'leaderboard'], true)) {
            throw ValidationException::withMessages(['kind' => 'Unsupported competition kind.']);
        }
        if ($idempotencyKey !== null && ($existing = CompetitionRecord::query()->where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }
        $record = $this->define($name, $data, $tenantId, $teamId);
        $record->update(['kind' => $kind, 'idempotency_key' => $idempotencyKey, 'status' => 'open']);

        return $record->fresh();
    }

    public function queue(CompetitionRecord $competition, string $actorId): CompetitionEntry
    {
        if ($competition->status !== 'open') {
            throw ValidationException::withMessages(['competition' => 'Competition is not accepting entries.']);
        }
        if (trim($actorId) === '') {
            throw ValidationException::withMessages(['actor' => 'An actor is required.']);
        }

        return $competition->entries()->firstOrCreate(['actor_id' => $actorId], ['status' => 'queued', 'rating' => 1000, 'wins' => 0, 'losses' => 0, 'points' => 0]);
    }

    public function match(CompetitionRecord $competition, string $playerA, string $playerB, ?string $idempotencyKey = null): CompetitionMatch
    {
        if ($playerA === $playerB || trim($playerA) === '' || trim($playerB) === '') {
            throw ValidationException::withMessages(['players' => 'Distinct players are required.']);
        }
        $this->queue($competition, $playerA);
        $this->queue($competition, $playerB);
        if ($idempotencyKey !== null && ($existing = CompetitionMatch::query()->where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }

        return $competition->matches()->create(['id' => (string) Str::uuid(), 'player_a' => $playerA, 'player_b' => $playerB, 'status' => 'active', 'idempotency_key' => $idempotencyKey]);
    }

    public function resolve(CompetitionMatch $match, string $winnerId, array $evidence = []): CompetitionMatch
    {
        if ($match->status !== 'active' || ! in_array($winnerId, [$match->player_a, $match->player_b], true)) {
            throw ValidationException::withMessages(['winner' => 'The match or winner is invalid.']);
        }

        return DB::transaction(function () use ($match, $winnerId, $evidence): CompetitionMatch {
            $winner = $match->competition->entries()->where('actor_id', $winnerId)->firstOrFail();
            $loserId = $match->player_a === $winnerId ? $match->player_b : $match->player_a;
            $loser = $match->competition->entries()->where('actor_id', $loserId)->firstOrFail();
            $winner->increment('wins');
            $winner->increment('points', 3);
            $winner->increment('rating', 25);
            $loser->increment('losses');
            $loser->increment('rating', -25);
            $match->update(['status' => 'resolved', 'winner_id' => $winnerId, 'evidence' => $evidence]);

            return $match->fresh();
        });
    }
}
