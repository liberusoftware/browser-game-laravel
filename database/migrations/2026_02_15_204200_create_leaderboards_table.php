<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // level, pvp_wins, quests, wealth
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->integer('value');
            $table->integer('rank')->nullable();
            $table->timestamp('snapshot_at');
            $table->timestamps();

            $table->index(['category', 'rank']);
            $table->index(['category', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
