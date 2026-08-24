<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_quests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('storyline')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->json('objectives');
            $table->json('prerequisites')->nullable();
            $table->json('branches')->nullable();
            $table->json('dialogue')->nullable();
            $table->json('rewards')->nullable();
            $table->boolean('repeatable')->default(false);
            $table->timestamps();
        });
        Schema::create('browser_game_quest_progress', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('quest_id')->constrained('browser_game_quests')->cascadeOnDelete();
            $table->string('actor_id')->index();
            $table->json('progress');
            $table->string('status')->default('in_progress')->index();
            $table->timestamps();
            $table->unique(['quest_id', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_quest_progress');
        Schema::dropIfExists('browser_game_quests');
    }
};
