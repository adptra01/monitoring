<?php

namespace App\Livewire;

use App\Models\UserTourProgress;
use Livewire\Attributes\On;
use Livewire\Component;

class TourManager extends Component
{
    #[On('markCompleted')]
    public function markCompleted(string $tourId): void
    {
        if (! auth()->check()) {
            return;
        }

        UserTourProgress::updateOrCreate(
            ['user_id' => auth()->id(), 'tour_id' => $tourId],
            ['completed_at' => now(), 'skipped_at' => null],
        );
    }

    #[On('markSkipped')]
    public function markSkipped(string $tourId): void
    {
        if (! auth()->check()) {
            return;
        }

        UserTourProgress::updateOrCreate(
            ['user_id' => auth()->id(), 'tour_id' => $tourId],
            ['completed_at' => null, 'skipped_at' => now()],
        );
    }

    public function render()
    {
        $tours = config('tours', []);
        $progress = collect();
        $routeName = null;

        if (auth()->check()) {
            $progress = UserTourProgress::where('user_id', auth()->id())
                ->get()
                ->keyBy('tour_id');

            $routeName = request()->route()?->getName();
        }

        return view('livewire.tour-manager', [
            'tours' => $tours,
            'progress' => $progress,
            'routeName' => $routeName,
        ]);
    }
}
