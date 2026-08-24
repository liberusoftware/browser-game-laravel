<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Player;
use App\Models\Player_Item;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BenchmarkInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:benchmark
                          {--players=10 : Number of players to create}
                          {--items=50 : Number of items per player}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark inventory performance with and without caching';

    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        parent::__construct();
        $this->inventoryService = $inventoryService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $playerCount = (int) $this->option('players');
        $itemsPerPlayer = (int) $this->option('items');

        $this->info('Starting Inventory Performance Benchmark');
        $this->info("Players: {$playerCount}, Items per player: {$itemsPerPlayer}");
        $this->newLine();

        // Setup test data
        $this->info('Setting up test data...');
        $players = $this->setupTestData($playerCount, $itemsPerPlayer);
        $this->info('✓ Test data created');
        $this->newLine();

        // Clear cache before testing
        Cache::flush();

        // Test 1: Query without service (N+1 problem)
        $this->info('Test 1: Loading inventory without eager loading (N+1 queries)');
        $this->benchmarkWithoutEagerLoading($players->first()->id);
        $this->newLine();

        // Test 2: Query with eager loading but no cache
        $this->info('Test 2: Loading inventory with eager loading, no cache');
        DB::enableQueryLog();
        $start = microtime(true);

        Player_Item::where('player_id', $players->first()->id)
            ->with('item')
            ->get();

        $time = (microtime(true) - $start) * 1000;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->line('  Time: '.number_format($time, 2).'ms');
        $this->line('  Queries: '.count($queries));
        $this->newLine();

        // Test 3: First load with service (cache miss)
        $this->info('Test 3: Service - First load (cache miss)');
        Cache::flush();
        DB::enableQueryLog();
        $start = microtime(true);

        $this->inventoryService->getPlayerInventory($players->first()->id);

        $time = (microtime(true) - $start) * 1000;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->line('  Time: '.number_format($time, 2).'ms');
        $this->line('  Queries: '.count($queries));
        $this->newLine();

        // Test 4: Second load with service (cache hit)
        $this->info('Test 4: Service - Second load (cache hit)');
        DB::enableQueryLog();
        $start = microtime(true);

        $this->inventoryService->getPlayerInventory($players->first()->id);

        $time = (microtime(true) - $start) * 1000;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->line('  Time: '.number_format($time, 2).'ms');
        $this->line('  Queries: '.count($queries));
        $this->line('  🚀 Cache performance boost!');
        $this->newLine();

        // Test 5: Multiple players (concurrent access simulation)
        $this->info("Test 5: Loading inventory for {$playerCount} players");
        Cache::flush();

        $start = microtime(true);
        DB::enableQueryLog();

        foreach ($players as $player) {
            $this->inventoryService->getPlayerInventory($player->id);
        }

        $timeFirst = (microtime(true) - $start) * 1000;
        $queriesFirst = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Now with cache
        $start = microtime(true);
        DB::enableQueryLog();

        foreach ($players as $player) {
            $this->inventoryService->getPlayerInventory($player->id);
        }

        $timeSecond = (microtime(true) - $start) * 1000;
        $queriesSecond = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->line('  First pass (no cache): '.number_format($timeFirst, 2)."ms, {$queriesFirst} queries");
        $this->line('  Second pass (cached): '.number_format($timeSecond, 2)."ms, {$queriesSecond} queries");
        $this->line('  Improvement: '.number_format(($timeFirst - $timeSecond) / $timeFirst * 100, 1).'%');
        $this->newLine();

        // Test 6: Inventory statistics
        $this->info('Test 6: Inventory statistics (with caching)');
        Cache::flush();

        DB::enableQueryLog();
        $start = microtime(true);
        $stats = $this->inventoryService->getInventoryStats($players->first()->id);
        $timeFirst = (microtime(true) - $start) * 1000;
        $queriesFirst = count(DB::getQueryLog());
        DB::disableQueryLog();

        DB::enableQueryLog();
        $start = microtime(true);
        $this->inventoryService->getInventoryStats($players->first()->id);
        $timeSecond = (microtime(true) - $start) * 1000;
        $queriesSecond = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->line('  First call: '.number_format($timeFirst, 2)."ms, {$queriesFirst} queries");
        $this->line('  Cached call: '.number_format($timeSecond, 2)."ms, {$queriesSecond} queries");
        $this->line('  Stats: '.json_encode($stats));
        $this->newLine();

        // Cleanup
        $this->info('Cleaning up test data...');
        Player_Item::whereIn('player_id', $players->pluck('id'))->delete();
        Player::whereIn('id', $players->pluck('id'))->delete();
        Cache::flush();

        $this->info('✓ Benchmark complete!');

        return Command::SUCCESS;
    }

    private function setupTestData(int $playerCount, int $itemsPerPlayer)
    {
        $items = Item::factory()->count(100)->create();
        $players = Player::factory()->count($playerCount)->create();

        foreach ($players as $player) {
            $selectedItems = $items->random($itemsPerPlayer);

            foreach ($selectedItems as $item) {
                Player_Item::create([
                    'player_id' => $player->id,
                    'item_id' => $item->id,
                    'quantity' => rand(1, 99),
                ]);
            }
        }

        return $players;
    }

    private function benchmarkWithoutEagerLoading(int $playerId)
    {
        DB::enableQueryLog();
        $start = microtime(true);

        $playerItems = Player_Item::where('player_id', $playerId)->get();

        // Access item relationship (triggers N+1)
        foreach ($playerItems as $playerItem) {
            $name = $playerItem->item?->name;
        }

        $time = (microtime(true) - $start) * 1000;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->line('  Time: '.number_format($time, 2).'ms');
        $this->line('  Queries: '.count($queries)." (1 + {$playerItems->count()} N+1 queries)");
        $this->line('  ⚠️  N+1 query problem!');
    }
}
