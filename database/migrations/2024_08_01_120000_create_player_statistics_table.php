<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade')->unique();
            $table->integer('total_quests_completed')->default(0);
            $table->integer('total_items_collected')->default(0);
            $table->integer('total_playtime_minutes')->default(0);
            $table->integer('highest_level_achieved')->default(1);
            $table->integer('total_experience_earned')->default(0);
            $table->integer('quests_in_progress')->default(0);
            $table->integer('achievements_unlocked')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_statistics');
    }
};
