<?php

use App\Models\ActivationRequest;
use App\Services\LicenseService;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $status = 'pending';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $request = ActivationRequest::findOrFail($id);
        app(LicenseService::class)->approveActivationRequest($request, auth()->id());
        session()->flash('message', 'Activation request approved.');
    }

    public function reject(int $id): void
    {
        $request = ActivationRequest::findOrFail($id);
        app(LicenseService::class)->rejectActivationRequest($request, 'Rejected by admin', auth()->id());
        session()->flash('message', 'Activation request rejected.');
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Activation Requests</h1>
        <select wire:model="status" class="rounded-md border-gray-300 shadow-sm">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('message') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">License</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Device</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach (ActivationRequest::when($status, fn($q) => $q->where('status', $status))->latest()->paginate(15) as $request)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $request->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm">{{ $request->license->key }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $request->device->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded @if($request->status->value === 'pending') bg-yellow-100 text-yellow-800 @elseif($request->status->value === 'approved') bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                                {{ $request->status->value }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm">{{ $request->code }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($request->status->value === 'pending')
                                <button wire:click="approve({{ $request->id }})"
                                        class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                <button wire:click="reject({{ $request->id }})"
                                        class="text-red-600 hover:text-red-900">Reject</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">{{ ActivationRequest::when($status, fn($q) => $q->where('status', $status))->latest()->paginate(15)->links() }}</div>
    </div>
</div>