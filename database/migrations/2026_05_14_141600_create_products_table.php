<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            // GitHub fields retained — used by products edit page for repo sync feature
            $table->unsignedBigInteger('github_repo_id')->nullable()->unique();
            $table->string('github_repo_full_name')->nullable();
            $table->string('github_repo_url')->nullable();
            $table->text('github_repo_description')->nullable();
            $table->string('github_default_branch')->nullable()->default('main');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
