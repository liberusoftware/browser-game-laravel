<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('browser_game_competition_matches', function (Blueprint $table): void {
            $table->dropUnique('browser_game_competition_matches_idempotency_key_unique');
            $table->unique(['competition_id', 'idempotency_key']);
        });
    }
};
