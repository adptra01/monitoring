<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component
{
    public string $name = '';
    public string $guard_name = 'web';
    public array $selectedPermissions = [];

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'required|string',
            'selectedPermissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ]);

        if (! empty($this->selectedPermissions)) {
            $role->syncPermissions($this->selectedPermissions);
        }

        session()->flash('message', 'Role created successfully.');
        $this->redirect('/admin/roles');
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Role</h1>
    </div>

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" wire:model="name"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Guard Name</label>
            <input type="text" wire:model="guard_name" readonly
                   class="w-full rounded-md border-gray-300 bg-gray-50 shadow-sm">
            @error('guard_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Permissions</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\Spatie\Permission\Models\Permission::all() as $perm)
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $perm->name }}"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm">
                        <span class="ml-2 text-sm text-gray-700">{{ $perm->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('selectedPermissions') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end">
            <a href="{{ url('/admin/roles') }}" class="px-4 py-2 text-gray-700 mr-2">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Create Role
            </button>
        </div>
    </form>
</div>
