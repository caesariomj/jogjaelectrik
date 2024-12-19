@extends('layouts.admin')

@section('title', 'Tambah Diskon')

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.discounts.index')"
                variant="secondary"
                class="me-4 !p-2 md:hidden"
                aria-label="Kembali ke halaman sebelumnya"
                wire:navigate
            >
                <svg
                    class="size-4 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </x-common.button>
            <h1 class="leading-none text-black">Tambah Diskon</h1>
        </header>
        <livewire:admin.discounts.discount-create-form />
    </section>
@endsection
