<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('github_repo_id')->nullable()->unique()->after('is_active');
            $table->string('github_repo_full_name')->nullable()->after('github_repo_id');
            $table->string('github_repo_url')->nullable()->after('github_repo_full_name');
            $table->text('github_repo_description')->nullable()->after('github_repo_url');
            $table->string('github_default_branch')->nullable()->default('main')->after('github_repo_description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'github_repo_id',
                'github_repo_full_name',
                'github_repo_url',
                'github_repo_description',
                'github_default_branch',
            ]);
        });
    }
};
