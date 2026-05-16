<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTourProgress extends Model
{
    protected $fillable = [
        'user_id',
        'tour_id',
        'completed_at',
        'skipped_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'skipped_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isSkipped(): bool
    {
        return $this->skipped_at !== null;
    }
}
