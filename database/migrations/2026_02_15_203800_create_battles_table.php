<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attacker_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('defender_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->string('battle_type'); // pvp, pve, boss
            $table->string('opponent_name')->nullable(); // for PvE
            $table->integer('opponent_level')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->json('battle_log')->nullable();
            $table->integer('experience_gained')->default(0);
            $table->integer('gold_gained')->default(0);
            $table->json('items_gained')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battles');
    }
};
