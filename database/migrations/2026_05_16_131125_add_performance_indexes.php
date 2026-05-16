<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->index(['product_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->index(['license_id', 'fingerprint']);
            $table->index('is_active');
        });

        Schema::table('activation_requests', function (Blueprint $table) {
            $table->index(['license_id', 'device_id', 'status']);
            $table->index(['device_id', 'status', 'activated_at']);
            $table->index('expires_at');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->index(['product_id', 'is_active']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['license_id', 'fingerprint']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('activation_requests', function (Blueprint $table) {
            $table->dropIndex(['license_id', 'device_id', 'status']);
            $table->dropIndex(['device_id', 'status', 'activated_at']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'is_active']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
        });
    }
};
