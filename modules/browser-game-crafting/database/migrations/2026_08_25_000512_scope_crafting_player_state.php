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

    public function down(): void
    {
        $uniqueIndexes = [
            'browser_game_crafting_resources' => ['browser_game_crafting_resources_actor_id_resource_key_tenant_id_team_id_unique', ['actor_id', 'resource_key']],
            'browser_game_crafting_professions' => ['browser_game_crafting_professions_actor_id_profession_tenant_id_team_id_unique', ['actor_id', 'profession']],
            'browser_game_crafting_discoveries' => ['browser_game_crafting_discoveries_actor_id_recipe_id_tenant_id_team_id_unique', ['actor_id', 'recipe_id']],
            'browser_game_crafting_queues' => ['browser_game_crafting_queues_actor_id_idempotency_key_tenant_id_team_id_unique', ['idempotency_key']],
        ];

        foreach ($uniqueIndexes as $tableName => [$scopedIndex, $legacyColumns]) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $scopedIndex): void {
                $table->dropUnique($scopedIndex);
                $table->dropIndex($tableName.'_tenant_id_index');
                $table->dropIndex($tableName.'_team_id_index');
                $table->dropColumn(['tenant_id', 'team_id']);
            });
            Schema::table($tableName, function (Blueprint $table) use ($legacyColumns): void {
                $table->unique($legacyColumns);
            });
        }
    }
};
