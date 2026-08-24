<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_collections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::create('browser_game_collection_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('collection_id')->constrained('browser_game_collections')->cascadeOnDelete();
            $table->string('entry_key');
            $table->string('name');
            $table->unsignedInteger('required_quantity')->default(1);
            $table->json('reward')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['collection_id', 'entry_key']);
        });
        Schema::create('browser_game_collection_progress', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->foreignUuid('collection_id')->constrained('browser_game_collections')->cascadeOnDelete();
            $table->string('entry_key');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['actor_id', 'collection_id', 'entry_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_collection_progress');
        Schema::dropIfExists('browser_game_collection_entries');
        Schema::dropIfExists('browser_game_collections');
    }
};
