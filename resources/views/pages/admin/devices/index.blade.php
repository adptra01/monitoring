<?php

use App\Models\Device;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Devices</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <input type="text" wire:model="search" placeholder="Search by fingerprint..."
               class="w-full rounded-md border-gray-300 shadow-sm">
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">License</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Seen</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach (Device::with('license')->when($search, fn($q) => $q->where('fingerprint', 'like', '%' . $search . '%'))->latest()->paginate(15) as $device)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $device->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm">{{ $device->license->key }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $device->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $device->platform }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $device->last_seen_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">{{ Device::with('license')->when($search, fn($q) => $q->where('fingerprint', 'like', '%' . $search . '%'))->latest()->paginate(15)->links() }}</div>
    </div>
</div>