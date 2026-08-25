<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_worlds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('team_id')->nullable()->index();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('browser_game_clocks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('world_id')->unique();
            $table->timestampTz('current_at');
            $table->decimal('speed', 12, 6)->default(1);
            $table->boolean('paused')->default(false);
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('world_id')->references('id')->on('browser_game_worlds')->cascadeOnDelete();
        });

        Schema::create('browser_game_rulesets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('world_id');
            $table->unsignedInteger('version');
            $table->string('status')->default('draft')->index();
            $table->json('rules');
            $table->timestampTz('published_at')->nullable();
            $table->uuid('published_by')->nullable();
            $table->timestamps();
            $table->unique(['world_id', 'version']);
            $table->foreign('world_id')->references('id')->on('browser_game_worlds')->cascadeOnDelete();
        });

        Schema::create('browser_game_content_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('world_id');
            $table->unsignedInteger('version');
            $table->string('status')->default('draft')->index();
            $table->string('content_hash', 128);
            $table->json('manifest');
            $table->timestampTz('published_at')->nullable();
            $table->uuid('published_by')->nullable();
            $table->timestamps();
            $table->unique(['world_id', 'version']);
            $table->foreign('world_id')->references('id')->on('browser_game_worlds')->cascadeOnDelete();
        });

        Schema::create('browser_game_feature_flags', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('world_id')->nullable();
            $table->string('key');
            $table->boolean('enabled')->default(false);
            $table->unsignedTinyInteger('rollout_percentage')->default(0);
            $table->json('constraints')->nullable();
            $table->uuid('changed_by')->nullable();
            $table->timestamps();
            $table->unique(['world_id', 'key']);
            $table->foreign('world_id')->references('id')->on('browser_game_worlds')->cascadeOnDelete();
        });

        Schema::create('browser_game_maintenance_states', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('world_id')->unique();
            $table->string('status')->default('resolved')->index();
            $table->text('message')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->uuid('changed_by')->nullable();
            $table->timestamps();
            $table->foreign('world_id')->references('id')->on('browser_game_worlds')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_maintenance_states');
        Schema::dropIfExists('browser_game_feature_flags');
        Schema::dropIfExists('browser_game_content_versions');
        Schema::dropIfExists('browser_game_rulesets');
        Schema::dropIfExists('browser_game_clocks');
        Schema::dropIfExists('browser_game_worlds');
    }
};
