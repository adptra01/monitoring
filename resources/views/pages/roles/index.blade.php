<?php

use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Flux\Flux;

use function Livewire\Volt\{uses, computed};
use function Laravel\Folio\{name};

name('roles.index');

uses(WithPagination::class);

$roles = computed(function () {
    return Role::with('permissions', 'users')->paginate(15);
});

$delete = function (int $id) {
    $role = Role::findOrFail($id);

    if ($role->name === 'admin') {
        Flux::toast(variant: 'danger', text: __('Cannot delete the admin role.'));
        return;
    }

    $role->delete();

    Flux::toast(variant: 'success', text: __('Role deleted successfully.'));
};

?>

<x-layouts::app :title="__('Roles')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Roles') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Roles') }}</flux:heading>
                <flux:subheading>{{ __('Manage system roles and permissions') }}</flux:subheading>
            </div>
            <flux:button variant="primary" icon="plus" href="{{ url('/admin/roles/create') }}">
                {{ __('Add Role') }}
            </flux:button>
        </div>

        @if (session()->has('error'))
            <flux:callout variant="danger">
                {{ session('error') }}
            </flux:callout>
        @endif

        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <flux:table :paginate="$this->roles">
                <flux:table.columns>
                    <flux:table.column>{{ __('ID') }}</flux:table.column>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Guard') }}</flux:table.column>
                    <flux:table.column>{{ __('Permissions') }}</flux:table.column>
                    <flux:table.column>{{ __('Users') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->roles as $role)
                        <flux:table.row :key="$role->id">
                            <flux:table.cell>{{ $role->id }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $role->name }}</flux:table.cell>
                            <flux:table.cell>{{ $role->guard_name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" inset="top bottom">
                                    {{ $role->permissions->count() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" inset="top bottom" color="blue">
                                    {{ $role->users->count() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button variant="ghost" size="sm" icon="pencil"
                                        href="{{ url('/admin/roles/' . $role->id . '/edit') }}" />
                                    @if ($role->name !== 'admin')
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $role->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this role?') }}" />
                                    @endif
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