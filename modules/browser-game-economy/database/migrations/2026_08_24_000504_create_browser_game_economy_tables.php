<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_economy', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable()->index();
            $table->string('kind')->default('currency')->index();
            $table->unsignedTinyInteger('precision')->default(0);
            $table->unsignedInteger('fee_basis_points')->default(0);
            $table->string('status')->default('active')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_economy_wallets', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->string('currency_code');
            $table->unsignedBigInteger('balance')->default(0);
            $table->timestamps();
            $table->unique(['actor_id', 'currency_code']);
        });

        Schema::create('browser_game_economy_ledger', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_id')->index();
            $table->string('currency_code')->index();
            $table->bigInteger('amount');
            $table->unsignedBigInteger('balance_after')->default(0);
            $table->string('entry_type')->index();
            $table->string('source')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_economy_vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_economy_vendor_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->constrained('browser_game_economy_vendors')->cascadeOnDelete();
            $table->string('item_key');
            $table->string('currency_code');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('stock')->nullable();
            $table->unsignedInteger('max_per_actor')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['vendor_id', 'item_key']);
        });

        Schema::create('browser_game_economy_listings', function (Blueprint $table): void {
            $table->id();
            $table->string('seller_id')->index();
            $table->string('buyer_id')->nullable()->index();
            $table->string('item_key');
            $table->string('currency_code');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('fee')->default(0);
            $table->string('status')->default('active')->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('sold_at')->nullable();
            $table->json('asset_reference')->nullable();
            $table->timestamps();
            $table->index(['item_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_economy_listings');
        Schema::dropIfExists('browser_game_economy_vendor_offers');
        Schema::dropIfExists('browser_game_economy_vendors');
        Schema::dropIfExists('browser_game_economy_ledger');
        Schema::dropIfExists('browser_game_economy_wallets');
        Schema::dropIfExists('browser_game_economy');
    }
};
