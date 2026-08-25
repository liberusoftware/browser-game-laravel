<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_competition_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('competition_id')->constrained('browser_game_competition')->cascadeOnDelete();
            $table->string('actor_id');
            $table->string('reward_key');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('claimed_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['competition_id', 'actor_id', 'reward_key']);
        });

        Schema::create('browser_game_competition_flags', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('competition_id')->constrained('browser_game_competition')->cascadeOnDelete();
            $table->foreignUuid('match_id')->nullable()->constrained('browser_game_competition_matches')->nullOnDelete();
            $table->string('actor_id');
            $table->string('reason');
            $table->string('status')->default('open')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_competition_flags');
        Schema::dropIfExists('browser_game_competition_rewards');
    }
};
