<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Services\CombatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CombatController extends Controller
{
    protected CombatService $combatService;

    public function __construct(CombatService $combatService)
    {
        $this->combatService = $combatService;
    }

    /**
     * Initiate a PvE battle.
     */
    public function pve(Request $request): JsonResponse
    {
        $request->validate([
            'opponent_name' => 'required|string|max:100',
            'opponent_level' => 'required|integer|min:1|max:100',
        ]);

        $player = $this->player($request);
        $battle = $this->combatService->initiatePvEBattle(
            $player,
            $request->input('opponent_name'),
            (int) $request->input('opponent_level')
        );

        return response()->json([
            'success' => true,
            'data' => $battle,
        ]);
    }

    /**
     * Initiate a PvP battle.
     */
    public function pvp(Request $request): JsonResponse
    {
        $request->validate([
            'defender_id' => 'required|integer|exists:players,id|different:attacker_id',
        ]);

        $attacker = $this->player($request);
        $defender = Player::findOrFail($request->input('defender_id'));
        abort_if($attacker->is($defender), 422, 'The defender must be a different player.');
        $battle = $this->combatService->initiatePvPBattle($attacker, $defender);

        return response()->json([
            'success' => true,
            'data' => $battle,
        ]);
    }

    /**
     * Heal a player to full health.
     */
    public function heal(Request $request): JsonResponse
    {
        $player = $this->player($request);
        $this->combatService->healPlayer($player);

        return response()->json([
            'success' => true,
            'message' => 'Player healed successfully',
            'data' => $player->fresh(['equipment']),
        ]);
    }

    private function player(Request $request): Player
    {
        $user = $request->user();
        abort_unless($user !== null, 403, 'Player authentication required.');

        $player = Player::query()->where('email', $user->email)->first();
        abort_unless($player !== null, 403, 'Player authentication required.');

        return $player;
    }
}
