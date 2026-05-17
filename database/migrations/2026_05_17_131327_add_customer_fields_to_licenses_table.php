<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('key');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('customer_store')->nullable()->after('customer_phone');
            $table->text('customer_address')->nullable()->after('customer_store');
            $table->timestamp('starts_at')->nullable()->after('notes');
            $table->json('devices')->nullable()->after('starts_at');
            $table->foreignId('user_id')->nullable()->change();
            $table->dropColumn(['mode', 'activated_at', 'metadata']);
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_phone', 'customer_store', 'customer_address', 'starts_at', 'devices']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->string('mode')->default('online');
            $table->timestamp('activated_at')->nullable();
            $table->json('metadata')->nullable();
        });
    }
};
