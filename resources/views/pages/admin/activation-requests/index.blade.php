<?php

use App\Models\ActivationRequest;
use function Livewire\Volt\{state};

state(['filter' => 'pending']);

$requests = fn() => ActivationRequest::with('license')
    ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
    ->latest()
    ->get();

$approve = function (int $id) {
    $request = ActivationRequest::findOrFail($id);
    $request->approve(auth()->id());
    session()->flash('success', 'Activation approved.');
};

$reject = function (int $id) {
    $request = ActivationRequest::findOrFail($id);
    $request->reject(auth()->id());
    session()->flash('success', 'Activation rejected.');
};
?>

<x-layouts.admin>
    <x-slot:header>Activation Requests</x-slot:header>

    <div class="mb-4">
        <select wire:model="filter"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="all">All</option>
        </select>
    </div>

    @if($requests->isEmpty())
        <p class="text-gray-500">No requests found.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="pb-3 pr-4">#</th>
                        <th class="pb-3 pr-4">License</th>
                        <th class="pb-3 pr-4">Old Device</th>
                        <th class="pb-3 pr-4">New Device</th>
                        <th class="pb-3 pr-4">Requested</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                        <tr class="border-b last:border-0">
                            <td class="py-3 pr-4">{{ $req->id }}</td>
                            <td class="py-3 pr-4 font-mono text-xs">{{ $req->license->license_key }}</td>
                            <td class="py-3 pr-4 font-mono text-xs">{{ $req->old_device_id ?? 'N/A' }}</td>
                            <td class="py-3 pr-4">
                                <div>{{ $req->new_device_name }}</div>
                                <div class="font-mono text-xs text-gray-500">{{ $req->new_device_id }}</div>
                            </td>
                            <td class="py-3 pr-4">{{ $req->requested_at->diffForHumans() }}</td>
                            <td class="py-3 pr-4">
                                <span @class([
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    'bg-yellow-100 text-yellow-800' => $req->status === 'pending',
                                    'bg-green-100 text-green-800' => $req->status === 'approved',
                                    'bg-red-100 text-red-800' => $req->status === 'rejected',
                                ])>{{ $req->status }}</span>
                            </td>
                            <td class="py-3">
                                @if($req->status === 'pending')
                                    <button wire:click="approve({{ $req->id }})"
                                        class="text-green-600 hover:underline font-medium">Approve</button>
                                    <button wire:click="reject({{ $req->id }})"
                                        class="text-red-600 hover:underline font-medium ml-2">Reject</button>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
