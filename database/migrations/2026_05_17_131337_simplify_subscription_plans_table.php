<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->default(30)->after('description');
            $table->dropColumn([
                'monthly_price',
                'yearly_price',
                'stripe_price_id_monthly',
                'stripe_price_id_yearly',
                'max_devices',
                'features',
                'is_default',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('duration_days');
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->string('stripe_price_id_monthly')->nullable();
            $table->string('stripe_price_id_yearly')->nullable();
            $table->unsignedInteger('max_devices')->default(1);
            $table->json('features')->nullable();
            $table->boolean('is_default')->default(false);
        });
    }
};
