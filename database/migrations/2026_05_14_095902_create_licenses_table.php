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
            $table->foreignId('product_id')->constrained();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('license_key', 36)->unique();
            $table->string('status', 20)->default('active');
            $table->integer('max_devices')->default(1);
            $table->date('started_at');
            $table->date('expired_at');
            $table->datetime('activated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('expired_at');
            $table->index(['status', 'expired_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
