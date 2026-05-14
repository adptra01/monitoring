<?php

use App\Models\License;
use App\Enums\LicenseStatus;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = License::with(['product', 'user']);

        if ($this->search) {
            $query->where('key', 'like', '%' . $this->search . '%');
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $licenses = $query->latest()->paginate(15);

        return view('admin.licenses.index', [
            'licenses' => $licenses,
            'statuses' => LicenseStatus::cases(),
        ]);
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Licenses</h1>
        <a href="{{ url('/admin/licenses/create') }}"
           class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Create License
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex gap-4">
            <input type="text" wire:model="search" placeholder="Search by key..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm">
            <select wire:model="status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('message') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Key</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Devices</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($licenses as $license)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm">{{ $license->key }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $license->product->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $license->user->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded @if($license->status === 'active') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                                {{ $license->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $license->devices->count() }}/{{ $license->max_devices }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $license->expires_at?->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">{{ $licenses->links() }}</div>
    </div>
</div>