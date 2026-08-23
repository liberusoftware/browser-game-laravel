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
        Schema::table('player__items', function (Blueprint $table) {
            // Add indexes for frequently queried columns to improve performance
            $table->index('player_id', 'idx_player_items_player_id');
            $table->index('item_id', 'idx_player_items_item_id');
            // Composite index for queries that filter by both player and item
            $table->index(['player_id', 'item_id'], 'idx_player_items_player_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player__items', function (Blueprint $table) {
            $table->dropIndex('idx_player_items_player_id');
            $table->dropIndex('idx_player_items_item_id');
            $table->dropIndex('idx_player_items_player_item');
        });
    }
};
