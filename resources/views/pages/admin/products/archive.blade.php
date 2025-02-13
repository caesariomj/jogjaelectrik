@extends('layouts.admin')

@section('title', 'Manajemen Arsip Produk')

@section('content')
    <section>
        <header class="mb-4 flex flex-col gap-4">
            <h1 class="text-black">Manajemen Arsip Produk</h1>
            <div class="pointer-events-auto">
                <div
                    class="rounded-md border border-yellow-400 bg-yellow-50 p-4 shadow-md"
                    role="alert"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    <div class="flex items-center" role="presentation">
                        <div class="flex-shrink-0" aria-hidden="true">
                            <svg class="size-4 text-yellow-800" viewBox="0 0 20 20" fill="currentColor">
                                <title>Ikon notifikasi informasi</title>
                                <path
                                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                                />
                            </svg>
                        </div>
                        <div role="heading" aria-level="2" class="ml-3 text-sm tracking-tight text-yellow-800">
                            <span class="font-semibold">Perhatian!</span>
                            Produk yang telah terjual tidak dapat dihapus dari sistem.
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <livewire:admin.products.product-table archived lazy />
    </section>
@endsection
