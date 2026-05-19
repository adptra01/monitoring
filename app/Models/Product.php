<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'github_repo_id',
        'github_repo_full_name',
        'github_repo_url',
        'github_repo_description',
        'github_default_branch',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
