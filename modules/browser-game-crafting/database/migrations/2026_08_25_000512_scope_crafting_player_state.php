<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        foreach (['browser_game_crafting_resources', 'browser_game_crafting_professions', 'browser_game_crafting_discoveries', 'browser_game_crafting_queues'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('tenant_id')->nullable()->index();
                $table->string('team_id')->nullable()->index();
            });
        }

        Schema::table('browser_game_crafting_resources', function (Blueprint $table): void {
            $table->dropUnique('browser_game_crafting_resources_actor_id_resource_key_unique');
            $table->unique(['actor_id', 'resource_key', 'tenant_id', 'team_id']);
        });

        Schema::table('browser_game_crafting_professions', function (Blueprint $table): void {
            $table->dropUnique('browser_game_crafting_professions_actor_id_profession_unique');
            $table->unique(['actor_id', 'profession', 'tenant_id', 'team_id']);
        });

        Schema::table('browser_game_crafting_discoveries', function (Blueprint $table): void {
            $table->dropUnique('browser_game_crafting_discoveries_actor_id_recipe_id_unique');
            $table->unique(['actor_id', 'recipe_id', 'tenant_id', 'team_id']);
        });

        Schema::table('browser_game_crafting_queues', function (Blueprint $table): void {
            $table->dropUnique('browser_game_crafting_queues_idempotency_key_unique');
            $table->unique(['actor_id', 'idempotency_key', 'tenant_id', 'team_id']);
        });
    }
};
