<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('code', 8);
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('activated_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('license_id');
            $table->index('device_id');
            $table->index('code');
            $table->index('status');
            $table->unique(['license_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_requests');
    }
};