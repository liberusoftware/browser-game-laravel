<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->integer('health')->default(100);
            $table->integer('max_health')->default(100);
            $table->integer('mana')->default(50);
            $table->integer('max_mana')->default(50);
            $table->integer('strength')->default(10);
            $table->integer('defense')->default(10);
            $table->integer('agility')->default(10);
            $table->integer('intelligence')->default(10);
            $table->integer('stat_points')->default(0);
            $table->timestamp('last_battle_at')->nullable();
            $table->timestamp('last_action_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'health', 'max_health', 'mana', 'max_mana',
                'strength', 'defense', 'agility', 'intelligence',
                'stat_points', 'last_battle_at', 'last_action_at',
            ]);
        });
    }
};
