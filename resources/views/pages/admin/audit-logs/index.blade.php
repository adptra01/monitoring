<?php

use App\Models\AuditLog;
use function Livewire\Volt\{state};

state(['actionFilter' => '']);

$actions = AuditLog::select('action')->distinct()->pluck('action');

$logs = fn() => AuditLog::with('user', 'license')
    ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter))
    ->latest('created_at')
    ->paginate(30);
?>

<x-layouts.admin>
    <x-slot:header>Audit Logs</x-slot:header>

    <div class="mb-4">
        <select wire:model="actionFilter"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            <option value="">All Actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}">{{ $action }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="pb-3 pr-4">Time</th>
                    <th class="pb-3 pr-4">Action</th>
                    <th class="pb-3 pr-4">User</th>
                    <th class="pb-3 pr-4">License</th>
                    <th class="pb-3">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr class="border-b last:border-0">
                        <td class="py-3 pr-4 text-xs whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td class="py-3 pr-4">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $log->action }}</span>
                        </td>
                        <td class="py-3 pr-4">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="py-3 pr-4 font-mono text-xs">{{ $log->license?->license_key ?? '-' }}</td>
                        <td class="py-3 text-xs">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</x-layouts.admin>
