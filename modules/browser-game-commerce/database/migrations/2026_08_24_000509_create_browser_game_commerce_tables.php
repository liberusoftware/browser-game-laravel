<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('browser_game_commerce', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_commerce_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('currency_code');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('stock')->nullable();
            $table->unsignedInteger('max_per_actor')->nullable();
            $table->json('delivery')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_commerce_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_id')->index();
            $table->string('currency_code');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');
            $table->string('status')->default('pending')->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('data')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_commerce_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('order_id')->constrained('browser_game_commerce_orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('browser_game_commerce_products');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->json('delivery')->nullable();
            $table->timestamps();
        });

        Schema::create('browser_game_commerce_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->string('actor_id')->index();
            $table->foreignUuid('order_id')->constrained('browser_game_commerce_orders');
            $table->foreignUuid('product_id')->constrained('browser_game_commerce_products');
            $table->string('delivery_key');
            $table->unsignedInteger('quantity');
            $table->string('status')->default('granted')->index();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['actor_id', 'order_id', 'product_id', 'delivery_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('browser_game_commerce_entitlements');
        Schema::dropIfExists('browser_game_commerce_order_lines');
        Schema::dropIfExists('browser_game_commerce_orders');
        Schema::dropIfExists('browser_game_commerce_products');
        Schema::dropIfExists('browser_game_commerce');
    }
};
