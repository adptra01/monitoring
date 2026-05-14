<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Flux\Flux;

use function Laravel\Folio\name;
use function Livewire\Volt\{state, mount};

name('admin.users.edit');

state([
    'user' => null,
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
    'is_admin' => false,
    'selectedRoles' => [],
    'directPermissions' => [],
]);

mount(function (string $user) {
    $this->user = User::with('roles', 'permissions')->findOrFail($user);
    $this->name = $this->user->name;
    $this->email = $this->user->email;
    $this->is_admin = $this->user->is_admin;
    $this->selectedRoles = $this->user->roles->pluck('name')->toArray();
    $this->directPermissions = $this->user->permissions->pluck('name')->toArray();
});

$save = function () {
    $this->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
        'password' => 'nullable|string|min:8|confirmed',
        'is_admin' => 'boolean',
        'selectedRoles' => 'nullable|array',
        'directPermissions' => 'nullable|array',
    ]);

    $this->user->update([
        'name' => $this->name,
        'email' => $this->email,
        'is_admin' => $this->is_admin,
    ]);

    if ($this->password) {
        $this->user->update(['password' => bcrypt($this->password)]);
    }

    $this->user->syncRoles($this->selectedRoles);
    $this->user->syncPermissions($this->directPermissions);

    Flux::toast(variant: 'success', text: __('User updated successfully.'));
    
    $this->redirect('/admin/users');
};

?>

<x-layouts::app :title="__('Edit User')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ url('/admin/users') }}">{{ __('Users') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Edit User') }}</flux:heading>
                    <flux:subheading>{{ __('Update details for :name', ['name' => $user->name]) }}</flux:subheading>
                </div>
            </div>

            <div class="max-w-2xl rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model="name" :label="__('Name')" required autofocus />
                    
                    <flux:input wire:model="email" type="email" :label="__('Email')" required />

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <flux:input wire:model="password" type="password" :label="__('New Password')" placeholder="{{ __('Leave blank to keep current') }}" />
                        <flux:input wire:model="password_confirmation" type="password" :label="__('Confirm New Password')" />
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Roles') }}</flux:label>
                        <div class="grid grid-cols-2 gap-4 mt-2">
                            @foreach (\Spatie\Permission\Models\Role::all() as $role)
                                <flux:checkbox wire:model="selectedRoles" :value="$role->name" :label="$role->name" />
                            @endforeach
                        </div>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Direct Permissions') }}</flux:label>
                        <div class="grid grid-cols-2 gap-4 mt-2">
                            @foreach (\Spatie\Permission\Models\Permission::all() as $perm)
                                <flux:checkbox wire:model="directPermissions" :value="$perm->name" :label="$perm->name" />
                            @endforeach
                        </div>
                    </flux:field>

                    <flux:checkbox wire:model="is_admin" :label="__('Administrator Access')" />

                    <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button href="{{ url('/admin/users') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Update User') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endvolt
</x-layouts::app>
