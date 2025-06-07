@extends('layouts.app')

@section('title', 'Tentang Kami')

@section('content')
    <section class="container mx-auto flex h-auto max-w-md flex-row justify-between gap-6 p-6 md:max-w-[96rem] md:p-12">
        <div class="w-full md:w-1/2">
            <h1 class="mb-4 text-black">Tentang Kami</h1>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                <strong>{{ config('app.name') }}</strong>
                adalah toko elektronik rumah tangga terpercaya yang berlokasi di
                <strong>{{ config('business.address') }}</strong>
                . Sejak berdiri, kami berkomitmen untuk menyediakan
                <strong>produk elektronik rumah tangga berkualitas</strong>
                dengan harga terjangkau dan pelayanan yang ramah.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">Apa yang Kami Tawarkan</h2>
            <p class="mb-2 text-base font-medium tracking-tight text-black">
                Kami menyediakan berbagai produk kebutuhan rumah tangga Anda seperti:
            </p>
            <ul class="mb-6 list-inside list-disc">
                @foreach ($primaryCategories as $category)
                    <li class="text-base font-medium tracking-tight text-black">{{ ucwords($category->name) }}</li>
                @endforeach

                <li class="text-base font-medium tracking-tight text-black">dan masih banyak lagi!</li>
            </ul>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">Belanja Lebih Nyaman Secara Online</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Kini Anda bisa berbelanja langsung melalui website ini,
                <strong>jogjaelectrik.com</strong>
                , tanpa perlu keluar rumah. Cukup pilih produk, isi data pengiriman, dan tunggu pesanan Anda sampai di
                rumah!
            </p>
            <p class="text-base font-medium tracking-tight text-black">
                Untuk pertanyaan lebih lanjut, silakan kunjungi
                <a href="{{ route('contact') }}" class="underline transition-colors hover:text-primary" wire:navigate>
                    halaman Kontak Kami
                </a>
                .
            </p>
        </div>
        <div class="hidden md:flex md:w-1/2 md:items-center md:justify-center">
            <div class="mx-auto mb-6 h-auto w-[28rem]">
                {!! file_get_contents(public_path('images/illustrations/help.svg')) !!}
            </div>
        </div>
    </section>
@endsection
