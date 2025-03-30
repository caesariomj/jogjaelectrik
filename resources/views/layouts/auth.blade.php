@extends('layouts.base')

@section('body')
    <main class="relative flex min-h-screen flex-row">
        <nav
            class="absolute start-0 top-0 z-50 flex w-full items-center bg-white p-0 shadow-lg md:w-auto md:bg-transparent md:shadow-none"
        >
            <a href="{{ route('home') }}" class="px-6 py-3 md:px-12 md:py-6" wire:navigate>
                <x-common.application-logo class="block h-9 w-auto fill-current text-primary md:h-12" />
            </a>
        </nav>
        <figure class="relative hidden w-full shrink md:block">
            <img
                src="https://placehold.co/600x400"
                alt="Signup background"
                class="absolute inset-0 h-full w-full object-cover opacity-90"
            />
            <div class="absolute inset-0 bg-black/50"></div>
            <figcaption class="relative z-10 flex h-full flex-col justify-end p-12">
                <h2 class="mb-6 text-4xl font-bold tracking-tight text-white">
                    Selamat Datang di {{ config('app.name') }}
                </h2>
                <p class="text-lg font-medium tracking-tight text-white">
                    Temukan produk elektronik rumah tangga terbaik dengan harga terjangkau di {{ config('app.name') }}.
                    Belanja mudah, aman, dan penuh penawaran menarik!
                </p>
            </figcaption>
        </figure>
        <section class="flex h-screen w-full shrink-0 flex-col overflow-y-auto md:w-[32rem]">
            <div class="mt-12 flex flex-grow items-center justify-center p-6 md:mt-0 md:p-12">
                {{ $slot }}
            </div>
        </section>
    </main>
@endsection
