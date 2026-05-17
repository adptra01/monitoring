<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('license_tokens');
        Schema::dropIfExists('activation_requests');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('user_tour_progress');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_clients');
        Schema::dropIfExists('audit_logs');
    }

    public function down(): void
    {
        // Tables are not recreated in down() to prevent data loss.
        // Run the original migration files to recreate them.
    }
};
