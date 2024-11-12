@extends('layouts.admin')

@section('title', 'Manajemen Subkategori')

@section('content')
    <section>
        <div class="mb-4 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <h1 class="text-black">Manajemen Subkategori</h1>
            @can('create subcategories')
                <x-common.button
                    :href="route('admin.subcategories.create')"
                    variant="primary"
                    class="w-full !px-6 md:w-fit"
                    wire:navigate
                >
                    <svg
                        class="size-5 opacity-75"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M5 12h14" />
                        <path d="M12 5v14" />
                    </svg>
                    Tambah
                </x-common.button>
            @endcan
        </div>
        <livewire:admin.subcategories.subcategory-table />
    </section>
@endsection
