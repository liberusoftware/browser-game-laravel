<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('misc')->index();
            $table->string('rarity')->default('common')->index();
            $table->string('slot')->nullable();
            $table->unsignedInteger('strength_bonus')->default(0);
            $table->unsignedInteger('defense_bonus')->default(0);
            $table->unsignedInteger('agility_bonus')->default(0);
            $table->unsignedInteger('intelligence_bonus')->default(0);
            $table->unsignedInteger('health_bonus')->default(0);
            $table->unsignedInteger('mana_bonus')->default(0);
            $table->unsignedInteger('max_durability')->nullable();
            $table->unsignedInteger('max_stack')->nullable();
            $table->unsignedInteger('min_level')->default(1);
            $table->unsignedBigInteger('sell_price')->default(0);
            $table->unsignedBigInteger('buy_price')->default(0);
            $table->string('status')->default('active')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_inventory', function (Blueprint $table): void {
            $table->id();
            $table->string('player_id')->index();
            $table->foreignUuid('item_id')->constrained('browser_game_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('equipment_slot')->nullable()->index();
            $table->unsignedInteger('durability')->nullable();
            $table->unsignedInteger('max_durability')->nullable();
            $table->boolean('is_bound')->default(false)->index();
            $table->foreignId('container_id')->nullable()->constrained('browser_game_inventory')->nullOnDelete();
            $table->timestamp('equipped_at')->nullable();
            $table->timestamp('bound_at')->nullable();
            $table->json('provenance')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_inventory');
        Schema::dropIfExists('browser_game_items');
    }
};
