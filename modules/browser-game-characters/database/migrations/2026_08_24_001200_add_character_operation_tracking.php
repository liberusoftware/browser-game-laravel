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
            $table->string('last_operation_key', 191)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('browser_game_characters', function (Blueprint $table): void {
            $table->dropColumn('last_operation_key');
        });
    }
};
