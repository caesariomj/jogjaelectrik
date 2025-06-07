@extends('layouts.base')

@section('title', 'Akses Ditolak')

@section('body')
    <section class="flex h-screen w-full items-center justify-center bg-white">
        <div class="mx-auto max-w-screen-xl px-4 py-8 lg:px-6 lg:py-16">
            <div class="mx-auto max-w-screen-sm text-center">
                <div class="mx-auto h-auto w-96">
                    {!! file_get_contents(public_path('images/illustrations/error.svg')) !!}
                </div>
                <h1 class="mb-4 text-3xl font-extrabold tracking-tight text-primary-600 lg:text-4xl">Akses Ditolak</h1>
                <p class="mb-4 text-lg font-normal tracking-tight text-black/70">
                    Maaf, kami tidak menampilkan halaman ini karena Anda tidak memiliki izin untuk mengaksesnya. Silakan
                    kembali ke halaman utama atau hubungi administrator jika Anda merasa ini adalah kesalahan.
                </p>
                <x-common.button :href="route('home')" variant="primary">
                    Kembali ke Halaman Utama
                    <svg
                        class="size-6 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M18 8L22 12L18 16" />
                        <path d="M2 12H22" />
                    </svg>
                </x-common.button>
            </div>
        </div>
    </section>
@endsection
