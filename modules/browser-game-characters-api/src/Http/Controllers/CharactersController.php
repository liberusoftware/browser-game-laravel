<?php

declare(strict_types=1);

namespace Liberu\BrowserGame\CharactersApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\BrowserGame\Characters\Models\GameCharacter;
use Liberu\BrowserGame\Characters\Support\CharactersManager;

final class CharactersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $playerId = (string) $request->user()->getAuthIdentifier();
        $team = $request->user()?->currentTeam;
        $characters = GameCharacter::query()->where('player_id', $playerId)->where('tenant_id', $team?->getAttribute('tenant_id'))->where('team_id', $team?->getKey())->latest()->paginate(min(max($request->integer('page[size]', $request->integer('page_size', 25)), 1), 100));

        return response()->json($characters->through(fn (GameCharacter $character): array => $this->resource($character)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'race' => ['required', 'string', 'max:80'], 'class' => ['required', 'string', 'max:80'], 'background' => ['nullable', 'string', 'max:120'], 'statistics' => ['array'], 'skills' => ['array']]);
        $team = $request->user()?->currentTeam;
        $character = app(CharactersManager::class)->create((string) $request->user()->getAuthIdentifier(), $data['name'], $data['race'], $data['class'], $data['background'] ?? null, $data['statistics'] ?? [], $data['skills'] ?? [], null, $team?->getKey(), $team?->getAttribute('tenant_id'));

        return response()->json(['data' => $this->resource($character)], 201);
    }

    public function update(Request $request, GameCharacter $character): JsonResponse
    {
        $character = $this->authorizeCharacter($request, $character);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'race' => ['required', 'string', 'max:80'],
            'class' => ['required', 'string', 'max:80'],
            'background' => ['nullable', 'string', 'max:120'],
        ]);
        $updated = app(CharactersManager::class)->updateProfile($character, $data['name'], $data['race'], $data['class'], $data['background'] ?? null);

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function show(Request $request, GameCharacter $character): JsonResponse
    {
        $character = $this->authorizeCharacter($request, $character);

        return response()->json(['data' => $this->resource($character)]);
    }

    public function respec(Request $request, GameCharacter $character): JsonResponse
    {
        $character = $this->authorizeCharacter($request, $character);
        $data = $request->validate(['skills' => ['required', 'array']]);
        $updated = app(CharactersManager::class)->respec($character, $data['skills'], $this->operationKey($request));

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function spendStats(Request $request, GameCharacter $character): JsonResponse
    {
        $character = $this->authorizeCharacter($request, $character);
        $data = $request->validate(['statistics' => ['required', 'array'], 'statistics.*' => ['integer', 'min:0']]);
        $updated = app(CharactersManager::class)->spendStatPoints($character, $data['statistics']);

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function skills(Request $request, GameCharacter $character): JsonResponse
    {
        $character = $this->authorizeCharacter($request, $character);
        $data = $request->validate(['skills' => ['required', 'array'], 'skills.*' => ['integer', 'min:0']]);
        $updated = app(CharactersManager::class)->allocateSkills($character, $data['skills'], $this->operationKey($request));

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function experience(Request $request, GameCharacter $character): JsonResponse
    {
        $character = $this->authorizeCharacter($request, $character);
        $data = $request->validate(['amount' => ['required', 'integer', 'min:0']]);
        $updated = app(CharactersManager::class)->awardExperience($character, $data['amount'], $this->operationKey($request));

        return response()->json(['data' => $this->resource($updated)]);
    }

    public function vitals(Request $request, GameCharacter $character): JsonResponse
    {
        $character = $this->authorizeCharacter($request, $character);
        $data = $request->validate(['health' => ['required', 'integer', 'min:0'], 'mana' => ['required', 'integer', 'min:0']]);
        $updated = app(CharactersManager::class)->updateVitals($character, $data['health'], $data['mana']);

        return response()->json(['data' => $this->resource($updated)]);
    }

    private function resource(GameCharacter $character): array
    {
        return ['id' => (string) $character->getKey(), 'type' => 'browser-game-characters', 'attributes' => [
            'name' => $character->name, 'race' => $character->race, 'class' => $character->class, 'background' => $character->background,
            'statistics' => $character->statistics, 'skills' => $character->skills, 'experience' => $character->experience,
            'level' => $character->level, 'health' => $character->health, 'max_health' => $character->max_health,
            'mana' => $character->mana, 'max_mana' => $character->max_mana,
            'base_stats' => ['strength' => $character->strength, 'defense' => $character->defense, 'agility' => $character->agility, 'intelligence' => $character->intelligence],
            'stat_points' => $character->stat_points,
            'available_skill_points' => $character->available_skill_points, 'respec_count' => $character->respec_count,
            'last_action_at' => $character->last_action_at?->toISOString(),
        ]];
    }

    private function authorizeCharacter(Request $request, GameCharacter $character): GameCharacter
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        $authorized = GameCharacter::query()
            ->whereKey($character->getKey())
            ->where('player_id', (string) $request->user()->getAuthIdentifier())
            ->where('tenant_id', $request->user()?->currentTeam?->getAttribute('tenant_id'))
            ->where('team_id', $teamId)
            ->first();
        abort_unless($authorized !== null, 404);

        return $authorized;
    }

    private function operationKey(Request $request): ?string
    {
        $key = $request->header('Idempotency-Key');

        return is_string($key) && trim($key) !== '' ? substr(trim($key), 0, 191) : null;
    }
}
