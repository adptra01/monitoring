<?php

use App\Models\User;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Flux\Flux;

use function Livewire\Volt\{uses, computed, state};
use function Laravel\Folio\{name};

name('users.index');

uses(WithPagination::class);

state([
    'role' => '',
]);

$users = computed(function () {
    $query = User::with('roles');

    if ($this->role) {
        $query->whereHas('roles', fn($q) => $q->where('name', $this->role));
    }

    return $query->latest()->paginate(15);
});

$delete = function (int $id) {
    $user = User::findOrFail($id);

    if ($user->isAdmin() && User::role('admin')->count() <= 1) {
        Flux::toast(variant: 'danger', text: __('Cannot delete the last admin user.'));
        return;
    }

    $user->delete();

    Flux::toast(variant: 'success', text: __('User deleted successfully.'));
};

?>

<x-layouts::app :title="__('Users')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Users') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Users') }}</flux:heading>
                <flux:subheading>{{ __('Manage system users and their roles') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ url('/admin/users/create') }}">
                {{ __('Add User') }}
            </flux:button>
        </div>

        {{-- Filters --}}
        <div class="flex gap-4">
            <flux:select wire:model.live="role" class="max-w-xs">
                <option value="">{{ __('All Roles') }}</option>
                @foreach (\Spatie\Permission\Models\Role::all() as $r)
                    <option value="{{ $r->name }}">{{ $r->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->users">
                <flux:table.columns>
                    <flux:table.column>{{ __('ID') }}</flux:table.column>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Roles') }}</flux:table.column>
                    <flux:table.column>{{ __('Admin') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->users as $user)
                        <flux:table.row :key="$user->id">
                            <flux:table.cell>{{ $user->id }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($user->roles as $role)
                                        <flux:badge size="sm" inset="top bottom">
                                            {{ $role->name }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$user->isAdmin() ? 'green' : 'gray'" size="sm" inset="top bottom">
                                    {{ $user->isAdmin() ? __('Yes') : __('No') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" icon="pencil"
                                        href="{{ url('/admin/users/' . $user->id . '/edit') }}" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $user->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this user?') }}" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
    @endvolt
</x-layouts::app>