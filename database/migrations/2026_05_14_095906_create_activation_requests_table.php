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
            $table->string('old_device_id')->nullable();
            $table->string('new_device_id');
            $table->string('new_device_name');
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 20)->default('pending');
            $table->datetime('requested_at');
            $table->datetime('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_requests');
    }
};
