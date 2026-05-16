<div
    id="tour-manager"
    data-tours="{{ json_encode(auth()->check() ? $tours : []) }}"
    data-progress="{{ json_encode(auth()->check() ? $progress->map(fn ($p) => [
        'tour_id' => $p->tour_id,
        'completed_at' => $p->completed_at?->toIso8601String(),
        'skipped_at' => $p->skipped_at?->toIso8601String(),
    ])->values() : []) }}"
    data-route="{{ auth()->check() ? $routeName : '' }}"
    wire:ignore
></div>
