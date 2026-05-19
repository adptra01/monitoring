<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Simplified: removed product_id FK, pricing fields (monthly_price, yearly_price,
            // stripe_price_id_*), max_devices, features, is_default — these were unused in the
            // current UI. Duration drives license expiry, set per-plan.
            $table->unsignedInteger('duration_days')->default(30);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
