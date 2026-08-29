<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('browser_game_quest_progress', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->dropUnique('browser_game_quest_progress_quest_id_actor_id_unique');
            $table->unique(['quest_id', 'actor_id', 'tenant_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::table('browser_game_quest_progress', function (Blueprint $table): void {
            $table->dropUnique('browser_game_quest_progress_quest_id_actor_id_tenant_id_team_id_unique');
            $table->dropIndex('browser_game_quest_progress_tenant_id_index');
            $table->dropIndex('browser_game_quest_progress_team_id_index');
            $table->dropColumn(['tenant_id', 'team_id']);
            $table->unique(['quest_id', 'actor_id']);
        });
    }
};
