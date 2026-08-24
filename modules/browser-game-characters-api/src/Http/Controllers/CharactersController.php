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
        $characters = GameCharacter::query()->where('player_id', $playerId)->latest()->paginate(min($request->integer('page_size', 25), 100));

        return response()->json(['data' => $characters->through(fn (GameCharacter $character): array => $this->resource($character))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'race' => ['required', 'string', 'max:80'], 'class' => ['required', 'string', 'max:80'], 'background' => ['nullable', 'string', 'max:120'], 'statistics' => ['array'], 'skills' => ['array']]);
        $character = app(CharactersManager::class)->create((string) $request->user()->getAuthIdentifier(), $data['name'], $data['race'], $data['class'], $data['background'] ?? null, $data['statistics'] ?? [], $data['skills'] ?? []);

        return response()->json(['data' => $this->resource($character)], 201);
    }

    public function show(Request $request, GameCharacter $character): JsonResponse
    {
        abort_unless($character->player_id === (string) $request->user()->getAuthIdentifier(), 404);

        return response()->json(['data' => $this->resource($character)]);
    }

    public function respec(Request $request, GameCharacter $character): JsonResponse
    {
        abort_unless($character->player_id === (string) $request->user()->getAuthIdentifier(), 404);
        $data = $request->validate(['skills' => ['required', 'array']]);
        $updated = app(CharactersManager::class)->respec($character, $data['skills']);

        return response()->json(['data' => $this->resource($updated)]);
    }

    private function resource(GameCharacter $character): array
    {
        return ['id' => (string) $character->getKey(), 'type' => 'browser-game-characters', 'attributes' => [
            'name' => $character->name, 'race' => $character->race, 'class' => $character->class, 'background' => $character->background,
            'statistics' => $character->statistics, 'skills' => $character->skills, 'experience' => $character->experience,
            'level' => $character->level, 'health' => $character->health, 'max_health' => $character->max_health,
            'available_skill_points' => $character->available_skill_points, 'respec_count' => $character->respec_count,
        ]];
    }
}
