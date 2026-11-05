<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Npc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NpcSeeder extends Seeder
{
    public function run(): void
    {
        app(StarterAdventureSeeder::class)->run();
        DB::transaction(function (): void {
            $items = Item::whereNotNull('starter_key')->pluck('id', 'starter_key');
            foreach (config('arena.npcs') as $npc) {
                Npc::firstOrCreate(['slug' => $npc['slug']], [
                    'name' => $npc['name'], 'description' => $npc['description'], 'region' => $npc['region'],
                    'difficulty' => $npc['difficulty'], 'level' => $npc['level'], 'behavior' => $npc['behavior'],
                    'stats' => ['health' => $npc['health'], 'mana' => 50 + $npc['level'], 'strength' => $npc['power'], 'defense' => $npc['power'], 'agility' => $npc['power'], 'intelligence' => $npc['power']],
                    'experience_reward' => $npc['xp'], 'gold_reward' => $npc['gold'], 'loot_item_id' => $items[$npc['loot']] ?? null,
                ]);
            }
        });
    }
}
