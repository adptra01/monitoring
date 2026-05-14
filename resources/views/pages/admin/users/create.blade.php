<?php

use App\Models\User;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $is_admin = false;
    public array $selectedRoles = [];

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
            'selectedRoles' => 'nullable|array',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'email_verified_at' => now(),
            'is_admin' => $this->is_admin,
        ]);

        if (! empty($this->selectedRoles)) {
            $user->assignRole($this->selectedRoles);
        }

        session()->flash('message', 'User created successfully.');
        $this->redirect('/admin/users');
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create User</h1>
    </div>

    <form wire:submit="save" class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" wire:model="name"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" wire:model="email"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" wire:model="password"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <input type="password" wire:model="password_confirmation"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Roles</label>
            <div class="space-y-2">
                @foreach (\Spatie\Permission\Models\Role::all() as $role)
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm">
                        <span class="ml-2 text-sm text-gray-700">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('selectedRoles') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" wire:model="is_admin" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                <span class="ml-2 text-sm text-gray-700">Admin Access</span>
            </label>
        </div>

        <div class="flex justify-end">
            <a href="{{ url('/admin/users') }}" class="px-4 py-2 text-gray-700 mr-2">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Create User
            </button>
        </div>
    </form>
</div>
