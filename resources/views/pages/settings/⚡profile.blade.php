<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\{state, computed, on, mount};

uses([ProfileValidationRules::class]);

state([
    'name' => '',
    'email' => '',
]);

mount(function () {
    $this->name = Auth::user()->name;
    $this->email = Auth::user()->email;
});

$updateProfileInformation = function () {
    $user = Auth::user();

    $validated = $this->validate($this->profileRules($user->id));

    $user->fill($validated);

    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }

    $user->save();

    Flux::toast(variant: 'success', text: __('Profile updated.'));
};

$resendVerificationNotification = function () {
    $user = Auth::user();

    if ($user->hasVerifiedEmail()) {
        $this->redirectIntended(default: route('dashboard', absolute: false));

        return;
    }

    $user->sendEmailVerificationNotification();

    Flux::toast(text: __('A new verification link has been sent to your email address.'));
};

$hasUnverifiedEmail = computed(function () {
    return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
});

$showDeleteUser = computed(function () {
    return ! Auth::user() instanceof MustVerifyEmail
        || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
});

?>

<x-layouts::app :title="__('Profile settings')">
    @volt
        <section class="w-full">
            @include('partials.settings-heading')

            <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

            <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
                <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
                    <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

                    <div>
                        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                        @if ($this->hasUnverifiedEmail)
                            <div>
                                <flux:text class="mt-4">
                                    {{ __('Your email address is unverified.') }}

                                    <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                        {{ __('Click here to re-send the verification email.') }}
                                    </flux:link>
                                </flux:text>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <flux:button variant="primary" type="submit" data-test="update-profile-button">
                            {{ __('Save') }}
                        </flux:button>
                    </div>
                </form>

                @if ($this->showDeleteUser)
                    <livewire:pages::settings.delete-user-form />
                @endif
            </x-pages::settings.layout>
        </section>
    @endvolt
</x-layouts::app>
