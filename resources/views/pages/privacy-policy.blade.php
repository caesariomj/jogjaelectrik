@extends('layouts.app')

@section('title', 'Kebijakan Privasi')

@section('content')
    <section class="container mx-auto h-auto max-w-md p-6 md:max-w-[96rem] md:p-12">
        <article id="privacy-policy">
            <h1 class="mb-4 text-black">Kebijakan Privasi</h1>
            <p class="mb-4 text-sm text-black/70">Terakhir diperbarui: 7 April 2025</p>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Toko Jogja Electrik menghargai dan melindungi privasi Anda sebagai pengguna situs kami. Kebijakan
                privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda
                saat mengakses situs
                <a href="{{ config('app.url') }}" class="underline transition-colors hover:text-primary" wire:navigate>
                    {{ config('app.url') }}
                </a>
                .
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">1. Informasi yang Kami Kumpulkan</h2>
            <ul class="mb-6 list-inside list-disc">
                <li class="text-base font-medium tracking-tight text-black">Nama lengkap</li>
                <li class="text-base font-medium tracking-tight text-black">Alamat tempat tinggal</li>
                <li class="text-base font-medium tracking-tight text-black">Kota dan provinsi tempat tinggal</li>
                <li class="text-base font-medium tracking-tight text-black">Kode pos</li>
                <li class="text-base font-medium tracking-tight text-black">Nomor telepon</li>
                <li class="text-base font-medium tracking-tight text-black">Alamat email</li>
            </ul>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">2. Cara Kami Menggunakan Informasi Anda</h2>
            <ul class="mb-6 list-inside list-disc">
                <li class="text-base font-medium tracking-tight text-black">Memproses dan mengirimkan pesanan</li>
                <li class="text-base font-medium tracking-tight text-black">
                    Menghubungi Anda terkait pesanan atau layanan
                </li>
                <li class="text-base font-medium tracking-tight text-black">Memberikan layanan pelanggan</li>
            </ul>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">3. Keamanan Data</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Kami menjaga data pribadi Anda dengan langkah-langkah keamanan teknis dan administratif yang layak untuk
                mencegah akses tidak sah, pengungkapan, atau perubahan data.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">4. Berbagi Informasi kepada Pihak Ketiga</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Kami tidak menjual atau menyewakan informasi pribadi Anda kepada pihak ketiga. Informasi hanya dibagikan
                kepada penyedia jasa pengiriman atau pembayaran yang bekerja sama dengan kami, sebatas yang diperlukan
                untuk menyelesaikan transaksi Anda.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">5. Hak Anda</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Anda berhak untuk mengakses, memperbarui, atau menghapus informasi pribadi Anda, serta menarik
                persetujuan penggunaan data yang sebelumnya telah diberikan.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">6. Kontak Kami</h2>
            <p class="text-base font-medium tracking-tight text-black">
                Jika Anda memiliki pertanyaan mengenai kebijakan privasi ini, silakan kunjungi
                <a href="{{ route('contact') }}" class="underline transition-colors hover:text-primary" wire:navigate>
                    halaman Kontak Kami
                </a>
                .
            </p>
        </article>
    </section>
@endsection
