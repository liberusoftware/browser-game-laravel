<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('npcs', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('region');
            $table->string('difficulty');
            $table->unsignedInteger('level');
            $table->json('stats');
            $table->string('behavior');
            $table->unsignedInteger('experience_reward');
            $table->unsignedInteger('gold_reward');
            $table->foreignId('loot_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::table('players', function (Blueprint $table): void {
            $table->boolean('arena_opt_in')->default(false)->index();
            $table->unsignedInteger('arena_rating')->default(1000)->index();
        });
        Schema::table('battles', function (Blueprint $table): void {
            $table->foreignId('npc_id')->nullable()->constrained('npcs')->nullOnDelete();
            $table->string('arena_status')->nullable()->index();
            $table->json('arena_state')->nullable();
            $table->unsignedInteger('arena_turn')->default(0);
            $table->foreignId('turn_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('outcome')->nullable();
            $table->integer('rating_change')->default(0);
            $table->index(['attacker_id', 'arena_status']);
            $table->index(['defender_id', 'arena_status']);
        });
    }

    public function down(): void
    {
        Schema::table('battles', function (Blueprint $table): void {
            $table->dropForeign(['npc_id']);
            $table->dropForeign(['turn_player_id']);
            $table->dropIndex(['attacker_id', 'arena_status']);
            $table->dropIndex(['defender_id', 'arena_status']);
            $table->dropColumn(['npc_id', 'arena_status', 'arena_state', 'arena_turn', 'turn_player_id', 'expires_at', 'outcome', 'rating_change']);
        });
        Schema::table('players', function (Blueprint $table): void {
            $table->dropColumn(['arena_opt_in', 'arena_rating']);
        });
        Schema::dropIfExists('npcs');
    }
};
