<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};

name('roles.edit');
middleware('check.admin');

state([
    'role' => null,
    'name' => '',
    'guard_name' => 'web',
    'selectedPermissions' => [],
]);

mount(function (string $role) {
    $this->role = Role::with('permissions')->findOrFail($role);
    $this->name = $this->role->name;
    $this->guard_name = $this->role->guard_name;
    $this->selectedPermissions = $this->role->permissions->pluck('name')->toArray();
});

$save = function () {
    $this->validate([
        'name' => 'required|string|max:255|unique:roles,name,' . $this->role->id,
        'guard_name' => 'required|string',
        'selectedPermissions' => 'nullable|array',
    ]);

    $this->role->update([
        'name' => $this->name,
        'guard_name' => $this->guard_name,
    ]);

    $this->role->syncPermissions($this->selectedPermissions);

    Flux::toast(duration: 1500, variant: 'success', text: __('Role updated successfully.'));

    $this->redirect('/roles');
};

?>

<x-layouts::app :title="__('Edit Role')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ url('/roles') }}">{{ __('Roles') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Edit Role') }}</flux:heading>
                    <flux:subheading>{{ __('Update details for :name', ['name' => $role->name]) }}</flux:subheading>
                </div>
            </div>

            <div class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model="name" :label="__('Role Name')" required autofocus />

                    <flux:input wire:model="guard_name" :label="__('Guard Name')" readonly />

                    <flux:field>
                        <flux:label>{{ __('Permissions') }}</flux:label>
                        <div class="grid grid-cols-2 gap-4 mt-2">
                            @foreach (\Spatie\Permission\Models\Permission::all() as $perm)
                                <flux:checkbox wire:model="selectedPermissions" :value="$perm->name"
                                    :label="$perm->name" />
                            @endforeach
                        </div>
                    </flux:field>

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ url('/roles') }}" variant="filled">{{ __('Cancel') }}</flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Update Role') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endvolt
</x-layouts::app>
