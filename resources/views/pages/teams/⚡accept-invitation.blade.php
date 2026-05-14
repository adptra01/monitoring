<?php

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Livewire\Volt\{state, mount};

state([
    'invitation' => null,
]);

mount(function (TeamInvitation $invitation) {
    $this->invitation = $invitation;
    $this->acceptInvitation();
});

$acceptInvitation = function () {
    $user = Auth::user();

    $this->validateInvitation($user, $this->invitation);

    DB::transaction(function () use ($user) {
        $team = $this->invitation->team;

        $team->memberships()->firstOrCreate(
            ['user_id' => $user->id],
            ['role' => $this->invitation->role]
        );

        $this->invitation->update(['accepted_at' => now()]);

        $user->switchTeam($team);
    });

    $this->redirectRoute('dashboard');
};

$validateInvitation = function (User $user, TeamInvitation $invitation) {
    if ($invitation->isAccepted()) {
        throw ValidationException::withMessages([
            'invitation' => [__('This invitation has already been accepted.')],
        ]);
    }

    if ($invitation->isExpired()) {
        throw ValidationException::withMessages([
            'invitation' => [__('This invitation has expired.')],
        ]);
    }

    if (Str::lower($invitation->email) !== Str::lower($user->email)) {
        throw ValidationException::withMessages([
            'invitation' => [__('This invitation was sent to a different email address.')],
        ]);
    }
};

?>

<x-layouts::app :title="__('Accept Invitation')">
    @volt
        <div class="flex items-center justify-center min-h-[400px]">
            <flux:spinner />
        </div>
    @endvolt
</x-layouts::app>
