<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('type'); // attack, defense, heal, buff
            $table->integer('mana_cost')->default(0);
            $table->integer('cooldown_seconds')->default(0);
            $table->integer('power')->default(0);
            $table->integer('min_level')->default(1);
            $table->timestamps();
        });

        Schema::create('player_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->integer('level')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_skills');
        Schema::dropIfExists('skills');
    }
};
