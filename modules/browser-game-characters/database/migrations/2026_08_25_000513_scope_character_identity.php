<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('browser_game_characters', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->index()->after('player_id');
            $table->dropUnique('browser_game_characters_player_id_name_unique');
            $table->unique(['player_id', 'name', 'tenant_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::table('browser_game_characters', function (Blueprint $table): void {
            $table->dropUnique('browser_game_characters_player_id_name_tenant_id_team_id_unique');
            $table->dropIndex('browser_game_characters_tenant_id_index');
            $table->dropColumn('tenant_id');
            $table->unique(['player_id', 'name']);
        });
    }
};
