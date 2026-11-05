<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Quest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StarterAdventureSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $items = [];
            foreach (config('starter-adventure.items') as $key => $attributes) {
                $items[$key] = Item::firstOrCreate(['starter_key' => $key], $attributes);
            }
            foreach (config('starter-adventure.quests') as $key => $quest) {
                Quest::firstOrCreate(['starter_key' => $key], [
                    'name' => $quest['name'],
                    'description' => $quest['description'],
                    'experience_reward' => $quest['experience_reward'],
                    'item_reward_id' => $items[$quest['reward']]->id,
                ]);
            }
        });
    }
}
