@extends('layouts.admin')

@section('title', 'Detail Kategori ' . ucwords($category->name))

@section('content')
    <section>
        <h1 class="mb-4 text-black">Detail Kategori &mdash; {{ ucwords($category->name) }}</h1>
        <dl class="grid grid-cols-1">
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Nama Kategori</dt>
                <dd class="w-full font-medium text-black md:w-2/3">{{ ucwords($category->name) }}</dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">
                    Kategori Utama
                    {{--
                        <x-tooltip
                        class="ms-1"
                        text="Kategori utama akan ditampilkan pada halaman utama. Anda hanya dapat menjadikan maksimal 2 kategori sebagai kategori utama."
                        />
                    --}}
                </dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ $category->is_primary ? 'Ya' : 'Tidak' }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Total Subkategori Terkait</dt>
                <dd class="w-full font-medium text-black md:w-2/3">{{ $category->subcategories_count }}</dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Total Produk Terkait</dt>
                <dd class="w-full font-medium text-black md:w-2/3">{{ $category->products_count }}</dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Ditambahkan Pada</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{-- {{ convertTimestamp($category->created_at) }} --}}
                    {{ $category->created_at }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{-- {{ convertTimestamp($category->updated_at) }} --}}
                    {{ $category->updated_at }}
                </dd>
            </div>
        </dl>
        <div class="mt-10 flex flex-col items-center gap-4 md:flex-row md:justify-end">
            @can('edit categories')
                <x-common.button
                    variant="secondary"
                    :href="route('admin.categories.edit', ['slug' => $category->slug])"
                    wire:navigate
                >
                    Edit
                </x-common.button>
            @endcan

            @can('delete categories')
                <x-common.button
                    variant="danger"
                    x-on:click.prevent="$dispatch('open-modal', 'confirm-category-deletion-{{ $category->id }}')"
                >
                    Hapus
                </x-common.button>
                @push('overlays')
                    <x-common.modal
                        name="confirm-category-deletion-{{ $category->id }}"
                        :show="$errors->isNotEmpty()"
                        focusable
                    >
                        <form
                            action="{{ route('admin.categories.destroy', ['category' => $category]) }}"
                            method="POST"
                            class="flex flex-col items-center p-6"
                        >
                            @csrf
                            @method('DELETE')
                            <div class="mb-4 rounded-full bg-red-100 p-4" aria-hidden="true">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="size-16 text-red-500"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <h2 class="mb-2 text-black">Hapus Kategori {{ ucwords($category->name) }}</h2>
                            <p class="mb-8 text-center text-base font-medium tracking-tight text-black/70">
                                Apakah anda yakin ingin menghapus kategori
                                <strong>"{{ strtolower($category->name) }}"</strong>
                                ini ? Proses ini tidak dapat dibatalkan, seluruh data yang terkait dengan kategori ini
                                akan dihapus dari sistem.
                            </p>
                            <div class="flex justify-end gap-4">
                                <x-common.button variant="secondary" x-on:click="$dispatch('close')">
                                    Batal
                                </x-common.button>
                                <x-common.button type="submit" variant="danger">Hapus Kategori</x-common.button>
                            </div>
                        </form>
                    </x-common.modal>
                @endpush
            @endcan
        </div>
    </section>
@endsection
