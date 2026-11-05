<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Player;
use App\Models\Quest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StarterAdventureService
{
    public function grantStarterKit(Player $player, string $characterClass): void
    {
        DB::transaction(function () use ($player, $characterClass): void {
            $player = Player::whereKey($player->id)->lockForUpdate()->firstOrFail();
            if ($player->starter_kit_granted_at !== null) {
                return;
            }

            $characterClass = $player->character_class ?? $characterClass;
            $definition = config('starter-adventure.classes')[$characterClass] ?? null;
            abort_unless($definition, 422);
            $kit = [$definition['weapon'] => 1, 'travel-cloak' => 1, 'silverleaf' => 3];
            $items = Item::whereIn('starter_key', array_keys($kit))->get()->keyBy('starter_key');
            $player->update(['character_class' => $characterClass]);
            if ($items->count() !== count($kit)) {
                return;
            }

            foreach ($kit as $key => $quantity) {
                $existing = $player->items()->whereKey($items[$key]->id)->first();
                $player->items()->syncWithoutDetaching([
                    $items[$key]->id => ['quantity' => ($existing?->pivot->quantity ?? 0) + $quantity],
                ]);
            }
            $player->update(['starter_kit_granted_at' => now()]);
        });
    }

    public function prerequisiteCompleted(Player $player, Quest $quest): bool
    {
        $definition = config('starter-adventure.quests')[$quest->starter_key] ?? null;
        if (! $definition || $definition['requires'] === null) {
            return true;
        }

        return $player->completedQuests()->where('starter_key', $definition['requires'])->exists();
    }

    public function complete(Player $player, Quest $quest, string $choice): array
    {
        return DB::transaction(function () use ($player, $quest, $choice): array {
            $player = Player::whereKey($player->id)->lockForUpdate()->firstOrFail();
            $definition = config('starter-adventure.quests')[$quest->starter_key] ?? null;
            abort_unless($definition, 404);
            if (! $player->activeQuests()->whereKey($quest->id)->exists()) {
                throw ValidationException::withMessages(['adventure' => 'Accept this quest before completing it. Completed quests cannot be claimed again.']);
            }
            if (! $this->prerequisiteCompleted($player, $quest)) {
                throw ValidationException::withMessages(['adventure' => 'Complete the previous chapter first.']);
            }
            if ($choice !== $definition['answer']) {
                throw ValidationException::withMessages(['adventure' => $definition['hint']]);
            }

            $rewards = app(QuestService::class)->completeQuest($player, $quest, $choice);
            $player->quests()->updateExistingPivot($quest->id, ['progress_percentage' => 100, 'completed_at' => now()]);

            return $rewards;
        });
    }
}
