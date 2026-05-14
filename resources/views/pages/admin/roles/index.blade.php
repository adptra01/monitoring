<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component
{
    use WithPagination;

    public function delete(int $id): void
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'admin') {
            session()->flash('error', 'Cannot delete the admin role.');
            return;
        }

        $role->delete();
        session()->flash('message', 'Role deleted successfully.');
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Roles</h1>
        <a href="{{ url('/admin/roles/create') }}"
           class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Add Role
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('message') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guard</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach (\Spatie\Permission\Models\Role::with('permissions')->paginate(15) as $role)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $role->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $role->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $role->guard_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-800">
                                {{ $role->permissions->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                {{ $role->users->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ url('/admin/roles/' . $role->id . '/edit') }}"
                               class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            @if ($role->name !== 'admin')
                                <button wire:click="delete({{ $role->id }})"
                                        wire:confirm="Are you sure you want to delete this role?"
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">{{ \Spatie\Permission\Models\Role::with('permissions')->paginate(15)->links() }}</div>
    </div>
</div>
