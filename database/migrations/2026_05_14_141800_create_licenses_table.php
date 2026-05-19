<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // user_id made nullable — licenses can be issued to customers without a user account
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key', 64)->unique();
            $table->string('status')->default('active');
            $table->unsignedInteger('max_devices')->default(1);
            $table->timestamp('expires_at')->nullable();

            // Removed: mode, activated_at, metadata — unused in current UI.
            // Added: customer_name, customer_phone, customer_store, customer_address for
            // direct license-to-customer assignment without requiring a user account.
            // Added: starts_at — license effective start date.
            // Added: devices (JSON) — replaces separate devices table; stores registered
            // device info (fingerprint, platform, last_seen_at) inline.
            $table->timestamp('starts_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_store')->nullable();
            $table->text('customer_address')->nullable();
            $table->json('devices')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
