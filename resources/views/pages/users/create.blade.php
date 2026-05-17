<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state};

name('users.create');
middleware('check.admin');

state([
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
    'selectedRoles' => [],
]);

$save = function () {
    $this->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'selectedRoles' => 'nullable|array',
    ]);

    $user = User::create([
        'name' => $this->name,
        'email' => $this->email,
        'password' => bcrypt($this->password),
        'email_verified_at' => now(),
    ]);

    if (!empty($this->selectedRoles)) {
        $user->assignRole($this->selectedRoles);
    }

    Flux::toast(duration: 1500, variant: 'success', text: __('User created successfully.'));

    $this->redirect(route('users.index'));
};

?>

<x-layouts::app :title="__('Create User')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ url('/users') }}">{{ __('Users') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Create') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Create User') }}</flux:heading>
                    <flux:subheading>{{ __('Add a new user to the system') }}</flux:subheading>
                </div>
            </div>

            <div class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model="name" :label="__('Name')" required autofocus />

                    <flux:input wire:model="email" type="email" :label="__('Email')" required />

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:input wire:model="password" type="password" :label="__('Password')" required />
                        <flux:input wire:model="password_confirmation" type="password" :label="__('Confirm Password')"
                            required />
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Roles') }}</flux:label>
                        <div class="grid grid-cols-2 gap-4 mt-2">
                            @foreach (\Spatie\Permission\Models\Role::all() as $role)
                                <flux:checkbox wire:model="selectedRoles" :value="$role->name" :label="$role->name" />
                            @endforeach
                        </div>
                    </flux:field>

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ url('/users') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Create User') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endvolt
</x-layouts::app>
