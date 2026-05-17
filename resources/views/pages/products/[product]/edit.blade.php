<?php

use App\Models\Product;
use App\Services\GitHubService;
use Illuminate\Support\Str;
use Flux\Flux;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{state, mount};

name('products.edit');
middleware('check.admin');

state([
    'product' => null,
    'name' => '',
    'slug' => '',
    'description' => '',
    'is_active' => true,
    'github_repo_full_name' => '',
    'selectedRepoData' => null,
    'repos' => [],
]);

mount(function (string $product) {
    $this->product = Product::where('slug', $product)->firstOrFail();
    $this->name = $this->product->name;
    $this->slug = $this->product->slug;
    $this->description = $this->product->description;
    $this->is_active = $this->product->is_active;
    $this->github_repo_full_name = $this->product->github_repo_full_name ?? '';

    if ($this->github_repo_full_name) {
        $this->selectedRepoData = collect(app(GitHubService::class)->listRepos())->firstWhere('full_name', $this->github_repo_full_name);
    }
});

$updatedName = function () {
    if ($this->name !== $this->product->name) {
        $this->slug = Str::slug($this->name);
    }
};

$updatedGithubRepoFullName = function () {
    if ($this->github_repo_full_name) {
        $this->selectedRepoData = collect(app(GitHubService::class)->listRepos())->firstWhere('full_name', $this->github_repo_full_name);
    } else {
        $this->selectedRepoData = null;
    }
};

$loadRepos = function () {
    $this->repos = app(GitHubService::class)->listRepos();
};

$syncRepos = function () {
    $gitHub = app(GitHubService::class);
    $gitHub->clearCache();
    $gitHub->fetchRepos();
    $this->repos = $gitHub->listRepos();

    Flux::toast(duration: 1500, variant: 'success', text: __('GitHub repositories synced.'));
};

$save = function () {
    $this->validate([
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:products,slug,' . $this->product->id,
        'description' => 'nullable|string',
        'is_active' => 'boolean',
        'github_repo_full_name' => 'nullable|string',
    ]);

    $data = $this->selectedRepoData;

    $this->product->update([
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        'is_active' => $this->is_active,
        'github_repo_id' => $data['id'] ?? $this->product->github_repo_id,
        'github_repo_full_name' => $data['full_name'] ?? null,
        'github_repo_url' => $data['url'] ?? null,
        'github_repo_description' => $data['description'] ?? null,
        'github_default_branch' => $data['default_branch'] ?? 'main',
    ]);

    Flux::toast(duration: 1500, variant: 'success', text: __('Product updated successfully.'));

    $this->redirect(route('products.index'));
};

?>

<x-layouts::app :title="__('Edit Product')">
    @volt
        <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl" x-init="$wire.loadRepos()">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('products.index') }}">{{ __('Products') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Edit Product') }}</flux:heading>
                    <flux:subheading>{{ __('Update details for :name', ['name' => $product->name]) }}</flux:subheading>
                </div>
            </div>

            <div class="w-full rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 bg-white dark:bg-zinc-800">
                <form wire:submit="save" class="space-y-6">
                    <flux:input wire:model.live.debounce.500ms="name" :label="__('Name')" required autofocus />

                    <flux:input wire:model="slug" :label="__('Slug')" required />

                    <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

                    <flux:checkbox wire:model="is_active" :label="__('Active')" />

                    {{-- GitHub Repository --}}
                    <div class="space-y-3 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium">{{ __('GitHub Repository') }}</p>
                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="syncRepos">
                                {{ __('Sync') }}
                            </flux:button>
                        </div>

                        @if (count($this->repos) > 0)
                            <flux:select wire:model="github_repo_full_name" :placeholder="__('Select a repository...')">
                                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                                @foreach ($this->repos as $repo)
                                    <flux:select.option value="{{ $repo['full_name'] }}">
                                        {{ $repo['full_name'] }} @if ($repo['private'])
                                            ({{ __('private') }})
                                        @endif
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <p class="text-xs text-zinc-400">{{ count($this->repos) }} {{ __('repositories available') }}
                            </p>
                        @else
                            <p class="text-sm text-zinc-400">
                                {{ __('No repositories found. Click Sync to load from GitHub.') }}
                            </p>
                        @endif
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button href="{{ route('products.index') }}" variant="filled">{{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary">{{ __('Update Product') }}</flux:button>
                    </div>
                </form>
            </div>
        </div>
    @endvolt
</x-layouts::app>
