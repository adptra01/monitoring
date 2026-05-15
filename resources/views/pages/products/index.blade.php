<?php

use App\Models\Product;
use App\Services\GitHubService;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\WithPagination;

use function Laravel\Folio\{name, middleware};
use function Livewire\Volt\{computed, state, uses};

name('products.index');
middleware('check.admin');


uses(WithPagination::class);

state([
    'search' => '',
    'sortBy' => 'id',
    'sortDirection' => 'asc',
])->url();

state([
    'showDetailModal' => false,
    'detailProduct' => null,
    'showDeleteModal' => false,
    'deletingProductId' => null,
]);

$sort = function ($column) {
    if ($this->sortBy === $column) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortBy = $column;
        $this->sortDirection = 'asc';
    }
};

$products = computed(function () {
    return Product::query()
        ->where(function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%')->orWhere('slug', 'like', '%' . $this->search . '%');
        })
        ->orderBy($this->sortBy, $this->sortDirection)
        ->paginate(10);
});

$totalProducts = computed(function () {
    return Product::count();
});

$activeProducts = computed(function () {
    return Product::where('is_active', true)->count();
});

$confirmDelete = function ($id) {
    $this->deletingProductId = $id;
    $this->showDeleteModal = true;
};

$toggleStatus = function ($id) {
    $product = Product::findOrFail($id);
    $product->update(['is_active' => ! $product->is_active]);

    Flux::toast(duration: 1500, variant: 'success', text: $product->is_active ? __('Product activated.') : __('Product deactivated.'));
};

$viewProduct = function ($id) {
    $this->detailProduct = Product::findOrFail($id);
    $this->showDetailModal = true;
};

$delete = function () {
    $product = Product::findOrFail($this->deletingProductId);
    $product->delete();

    $this->deletingProductId = null;
    $this->showDeleteModal = false;

    Flux::toast(duration: 1500, variant: 'success', text: __('Product deleted.'));
};

$syncRepos = function () {
    $gitHub = app(GitHubService::class);
    $repos = $gitHub->fetchRepos();

    if (empty($repos)) {
        Flux::toast(duration: 3000, variant: 'warning', text: __('No repositories found. Check your GitHub token.'));

        return;
    }

    $gitHub->clearCache();
    $gitHub->listRepos();

    $created = 0;

    foreach ($repos as $repo) {
        $product = Product::where('github_repo_id', $repo['id'])->first();

        $readme = $gitHub->fetchReadme($repo['full_name']);

        if ($product === null) {
            Product::create([
                'name' => $repo['full_name'],
                'slug' => Str::slug($repo['full_name']),
                'description' => $readme ?? $repo['description'],
                'is_active' => true,
                'github_repo_id' => $repo['id'],
                'github_repo_full_name' => $repo['full_name'],
                'github_repo_url' => $repo['url'],
                'github_repo_description' => $repo['description'],
                'github_default_branch' => $repo['default_branch'],
            ]);

            $created++;
        } elseif ($readme !== null) {
            $product->update(['description' => $readme]);
        }
    }

    Flux::toast(duration: 3000, variant: 'success', text: __(':count repositories synced as products.', ['count' => $created]));
};

?>

<x-layouts::app :title="__('Products')">
    @volt
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('dashboard') }}">{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Products') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">{{ __('Products') }}</flux:heading>
                <flux:subheading>{{ __('Manage your product inventory') }}</flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" icon="arrow-path" wire:click="syncRepos">
                    {{ __('Sync GitHub') }}
                </flux:button>
                <flux:button variant="primary" icon="plus" href="{{ route('products.create') }}">
                    {{ __('Add Product') }}
                </flux:button>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Total Products') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ $this->totalProducts }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Active') }}</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $this->activeProducts }}
                </p>
            </div>
        </div>

        {{-- Search --}}
        <flux:input size="md" wire:model.live="search" type="search"
            placeholder="{{ __('Search by name or slug...') }}" />
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">

            {{-- Table --}}
            <flux:table :paginate="$this->products">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                        wire:click="sort('name')">
                        {{ __('Name') }}
                    </flux:table.column>

                    <flux:table.column sortable :sorted="$sortBy === 'slug'" :direction="$sortDirection"
                        wire:click="sort('slug')">
                        {{ __('Slug') }}
                    </flux:table.column>

                    <flux:table.column>
                        {{ __('GitHub Repository') }}
                    </flux:table.column>

                    <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection"
                        wire:click="sort('is_active')">
                        {{ __('Status') }}
                    </flux:table.column>

                    <flux:table.column>
                        {{ __('Actions') }}
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->products as $product)
                        <flux:table.row :key="$product->id">
                            <flux:table.cell class="font-medium">{{ $product->name }}</flux:table.cell>

                            <flux:table.cell variant="strong">
                                <code class="text-xs">{{ $product->slug }}</code>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($product->github_repo_full_name)
                                    <a href="{{ $product->github_repo_url }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        <flux:icon name="folder" variant="micro" class="size-3.5" />
                                        {{ $product->github_repo_full_name }}
                                    </a>
                                @else
                                    <span
                                        class="text-xs text-zinc-400">{{ __('Not linked') }}</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:button size="sm" variant="primary" :color="$product->is_active ? 'emerald' : 'red'"
                                    wire:click="toggleStatus({{ $product->id }})">
                                    {{ $product->is_active ? __('Active') : __('Inactive') }}
                                </flux:button>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="viewProduct({{ $product->id }})">
                                            {{ __('View') }}
                                        </flux:menu.item>
                                        <flux:menu.item icon="pencil"
                                            href="{{ route('products.edit', ['product' => $product->slug]) }}">
                                            {{ __('Edit') }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger"
                                            wire:click="confirmDelete({{ $product->id }})">
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

        </div>

        {{-- Detail Modal --}}
        <flux:modal wire:model.self="showDetailModal" class="max-w-4xl w-full">
            @if ($detailProduct)
                <div class="space-y-8">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <flux:heading size="xl" class="truncate">{{ $detailProduct->name }}</flux:heading>
                            <flux:subheading>{{ __('Product details') }}</flux:subheading>
                        </div>
                        <flux:badge :color="$detailProduct->is_active ? 'emerald' : 'red'" size="lg">
                            {{ $detailProduct->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-1 gap-y-6">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-zinc-400">{{ __('Slug') }}
                            </p>
                            <p class="mt-1.5">
                                <code
                                    class="rounded-md bg-zinc-100 px-2 py-0.5 text-sm dark:bg-zinc-700">{{ $detailProduct->slug }}</code>
                            </p>
                        </div>

                        @if ($detailProduct->description)
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400">
                                    {{ __('Description') }}
                                </p>
                                <p class="mt-1.5 text-sm">{{ $detailProduct->description }}</p>
                            </div>
                        @endif

                        @if ($detailProduct->github_repo_full_name)
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400">
                                    {{ __('GitHub Repository') }}
                                </p>
                                <p class="mt-1.5">
                                    <a href="{{ $detailProduct->github_repo_url }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        <flux:icon name="folder" variant="micro" class="size-4" />
                                        {{ $detailProduct->github_repo_full_name }}
                                    </a>
                                </p>
                                @if ($detailProduct->github_repo_description)
                                    <p class="mt-1 text-sm text-zinc-500">
                                        {{ $detailProduct->github_repo_description }}
                                    </p>
                                @endif
                                <p class="mt-1 text-xs text-zinc-400">
                                    {{ __('Default branch') }}:
                                    <code
                                        class="rounded bg-zinc-100 px-1 py-0.5 dark:bg-zinc-700">{{ $detailProduct->github_default_branch ?? 'main' }}</code>
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between">
                        <div class="flex gap-6 text-xs text-zinc-400">
                            <span>{{ __('Created') }} {{ $detailProduct->created_at->format('d M Y, H:i') }}</span>
                            <span>{{ __('Updated') }} {{ $detailProduct->updated_at->format('d M Y, H:i') }}</span>
                        </div>
                        <flux:modal.close>
                            <flux:button variant="filled">{{ __('Close') }}</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            @endif
        </flux:modal>

        {{-- Delete Confirmation Modal --}}
        <flux:modal wire:model.self="showDeleteModal" class="max-w-lg">
            <form wire:submit="delete" class="space-y-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="micro" class="text-red-600" />
                    </div>
                    <div>
                        <flux:heading size="lg">{{ __('Delete Product') }}</flux:heading>
                        <flux:subheading class="mt-1">
                            {{ __('Are you sure you want to delete this product? This action cannot be undone.') }}
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </div>
    @endvolt
</x-layouts::app>
