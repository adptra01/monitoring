<?php

use App\Models\AuditLog;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $action = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex gap-4">
            <input type="text" wire:model="search" placeholder="Search..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm">
            <select wire:model="action" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All Actions</option>
                @foreach (['created', 'device_registered', 'activation_request_created', 'activation_approved', 'activation_rejected', 'suspended', 'revoked', 'restored'] as $act)
                    <option value="{{ $act }}">{{ $act }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach (AuditLog::with('user')->when($action, fn($q) => $q->where('action', 'like', '%' . $action . '%'))->latest()->paginate(25) as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $log->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $log->action }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $log->user?->email ?? 'System' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $log->ip_address }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">{{ AuditLog::with('user')->when($action, fn($q) => $q->where('action', 'like', '%' . $action . '%'))->latest()->paginate(25)->links() }}</div>
    </div>
</div>