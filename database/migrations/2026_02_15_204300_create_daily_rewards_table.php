<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('daily_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->date('reward_date');
            $table->integer('day_streak')->default(1);
            $table->integer('gold_rewarded')->default(0);
            $table->integer('experience_rewarded')->default(0);
            $table->json('items_rewarded')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'reward_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_rewards');
    }
};
