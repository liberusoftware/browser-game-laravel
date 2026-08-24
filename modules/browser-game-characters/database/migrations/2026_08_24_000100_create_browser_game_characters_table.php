<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_characters', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('player_id')->index();
            $table->uuid('world_id')->nullable()->index();
            $table->uuid('team_id')->nullable()->index();
            $table->string('name');
            $table->string('race', 80);
            $table->string('class', 80);
            $table->string('background', 120)->nullable();
            $table->json('statistics');
            $table->json('skills');
            $table->unsignedBigInteger('experience')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('health')->default(100);
            $table->unsignedInteger('max_health')->default(100);
            $table->unsignedInteger('mana')->default(50);
            $table->unsignedInteger('max_mana')->default(50);
            $table->unsignedInteger('strength')->default(10);
            $table->unsignedInteger('defense')->default(10);
            $table->unsignedInteger('agility')->default(10);
            $table->unsignedInteger('intelligence')->default(10);
            $table->unsignedInteger('stat_points')->default(0);
            $table->unsignedInteger('available_skill_points')->default(0);
            $table->unsignedInteger('respec_count')->default(0);
            $table->timestamp('last_battle_at')->nullable();
            $table->timestamp('last_action_at')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_characters');
    }
};
