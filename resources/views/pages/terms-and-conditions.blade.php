@extends('layouts.app')

@section('title', 'Syarat dan Ketentuan')

@section('content')
    <section class="container mx-auto h-auto max-w-md p-6 md:max-w-[96rem] md:p-12">
        <article id="terms-conditions">
            <h1 class="mb-4 text-black">Syarat & Ketentuan</h1>
            <p class="mb-4 text-sm text-black/70">Terakhir diperbarui: 7 April 2025</p>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Dengan mengakses dan menggunakan situs Jogja Electrik di
                <a href="{{ config('app.url') }}" class="underline transition-colors hover:text-primary" wire:navigate>
                    {{ config('app.url') }}
                </a>
                , Anda menyetujui untuk terikat oleh Syarat dan Ketentuan berikut.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">1. Akun Pengguna</h2>
            <ul class="mb-6 list-inside list-disc">
                <li class="text-base font-medium tracking-tight text-black">
                    Anda dapat membuat akun untuk melakukan pemesanan produk.
                </li>
                <li class="text-base font-medium tracking-tight text-black">
                    Anda bertanggung jawab menjaga keamanan akun Anda, termasuk kerahasiaan password.
                </li>
                <li class="text-base font-medium tracking-tight text-black">
                    Informasi yang Anda berikan harus akurat dan terkini.
                </li>
            </ul>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">2. Pembelian Produk</h2>
            <ul class="mb-6 list-inside list-disc">
                <li class="text-base font-medium tracking-tight text-black">
                    Produk yang dijual merupakan barang fisik seperti elektronik rumah tangga dan perabotan ringan.
                </li>
                <li class="text-base font-medium tracking-tight text-black">
                    Semua harga yang tertera termasuk pajak (jika berlaku), tetapi belum termasuk ongkos kirim.
                </li>
                <li class="text-base font-medium tracking-tight text-black">
                    Pesanan akan diproses setelah pembayaran dikonfirmasi.
                </li>
            </ul>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">3. Pembatalan dan Pengembalian</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Silakan hubungi kami jika terjadi kesalahan pemesanan atau ingin membatalkan sebelum barang dikirim.
                Produk yang sudah dibuka atau digunakan tidak dapat dikembalikan kecuali terdapat kerusakan dari pabrik.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">4. Hak Kekayaan Intelektual</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Seluruh konten situs termasuk teks, gambar, dan logo adalah milik Toko Jogja Electrik dan dilindungi
                oleh undang-undang hak cipta.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">5. Pembatasan Tanggung Jawab</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Kami tidak bertanggung jawab atas kerusakan tidak langsung, insidental, atau konsekuensial yang timbul
                dari penggunaan situs ini.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">6. Perubahan Ketentuan</h2>
            <p class="mb-6 text-base font-medium tracking-tight text-black">
                Toko Jogja Electrik berhak untuk mengubah ketentuan ini kapan saja. Perubahan akan diumumkan melalui
                situs kami dan berlaku segera setelah dipublikasikan.
            </p>
            <h2 class="mb-4 text-pretty !text-2xl leading-none text-black">7. Hukum yang Berlaku</h2>
            <p class="text-base font-medium tracking-tight text-black">
                Syarat dan ketentuan ini tunduk pada hukum yang berlaku di Republik Indonesia.
            </p>
        </article>
    </section>
@endsection
