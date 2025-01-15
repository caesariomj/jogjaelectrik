@php
    $faqItems = [
        ['id' => 1, 'title' => '1. Apa itu Toko Jogja Electrik', 'content' => 'Toko Jogja Electrik adalah toko yang menyediakan berbagai produk elektronik rumahan berkualitas. Kami menawarkan peralatan dapur, perangkat rumah tangga, serta alat elektronik lainnya. Kami berkomitmen untuk menyediakan barang berkualitas dengan harga bersaing dan pelayanan terbaik.'],
        ['id' => 2, 'title' => '2. Apakah Toko Jogja Electrik memiliki toko fisik?', 'content' => 'Ya, lokasi kami berada di Yogyakarta, di Jalan-jalan di akhir pekan.'],
        ['id' => 3, 'title' => '3. Apa saja produk yang dijual di Toko Jogja Electrik?', 'content' => 'Kami menjual berbagai jenis produk elektronik rumahan, termasuk peralatan dapur seperti blender, mixer, dan lain-lain, serta perangkat rumah tangga seperti setrika, kipas angin, hingga alat elektronik lainnya. Untuk lebih lengkapnya anda dapat melihatnya di menu produk.'],
        ['id' => 4, 'title' => '4. Apakah produk yang dijual asli dan bergaransi?', 'content' => 'Ya, semua produk yang kami jual adalah 100% asli dan berasal dari produsen terpercaya. Setiap produk juga dilengkapi dengan garansi resmi dari pabrik sesuai dengan ketentuan yang berlaku, yang memberikan Anda perlindungan tambahan jika terjadi kerusakan atau masalah pada produk.'],
        ['id' => 5, 'title' => '5. Jam operasional Toko Jogja Electrik?', 'content' => 'Toko Jogja Electrik beroperasi secara online 24 jam. Namun, untuk layanan pelanggan kami tersedia setiap hari pukul 08:00 - 21:00 WIB. Anda dapat menghubungi kami selama jam operasional tersebut untuk bantuan lebih lanjut.'],
        ['id' => 6, 'title' => '6. Bagaimana cara memesan produk?', 'content' => 'Untuk melakukan pemesanan, Anda perlu membuat akun terlebih dahulu. Selanjutnya anda cukup memilih produk yang Anda inginkan di website kami, atur jumlah yang Anda inginkan lalu klik "Tambah ke Keranjang", dan lanjutkan ke halaman checkout. Anda akan diminta untuk memasukkan informasi pengiriman dan memilih metode pembayaran yang diinginkan. Setelah pembayaran dikonfirmasi, pesanan Anda akan diproses dan segera dikirim.'],
        ['id' => 7, 'title' => '7. Apakah saya bisa membatalkan pesanan?', 'content' => 'Pesanan dapat dibatalkan sebelum pesanan dikirim. Jika Anda ingin membatalkan pesanan, silakan akses halaman "Pesanan Saya" lalu klik tombol "Batalkan Pesanan" dan pilih alasan pembatalan pesanan Anda.'],
        ['id' => 8, 'title' => '8. Apa saja metode pembayaran yang tersedia?', 'content' => 'Kami menerima berbagai metode pembayaran, termasuk virtual akun bank, serta beberapa metode pembayaran digital seperti e-wallet. Semua transaksi dijamin aman karena melalui payment gateway Xendit dan dengan enkripsi SSL untuk melindungi data pribadi Anda.'],
        ['id' => 9, 'title' => '9. Apa saja layanan ekspedisi pengiriman yang tersedia?', 'content' => 'Kami menggunakan beberapa ekspedisi pengiriman terpercaya untuk pengiriman produk Anda, termasuk JNE, TIKI, dan POS INDONESIA. Anda dapat memilih layanan pengiriman yang sesuai dengan preferensi Anda saat checkout.'],
        ['id' => 10, 'title' => '10. Berapa lama waktu pengiriman?', 'content' => 'Waktu pengiriman dapat bervariasi tergantung pada lokasi Anda dan pilihan ekspedisi yang digunakan. Anda dapat melihat estimasi waktu pengiriman ketika memilih ekspedisi beserta layanan-nya pada saat Anda melakukan checkout.'],
        ['id' => 11, 'title' => '11. Apakah saya bisa melacak pesanan saya?', 'content' => 'Ya, setelah pesanan Anda dikirim, kami akan memberikan nomor resi yang dapat Anda gunakan untuk melacak status pengiriman. Anda bisa melacak pesanan melalui situs web ekspedisi yang anda pilih untuk mengirim pesanan Anda.'],
        ['id' => 12, 'title' => '11. Bagaimana kebijakan pengembalian barang?', 'content' => 'Kami menerima pengembalian barang dalam waktu 7 hari setelah barang diterima, dengan syarat produk dalam kondisi asli, belum digunakan, dan masih dalam kemasan aslinya. Produk yang rusak atau cacat saat diterima dapat dikembalikan untuk penggantian sesuai dengan ketentuan garansi. Anda dapat menghubungi layanan pelanggan kami untuk melakukan pengembalian barang.'],
        ['id' => 13, 'title' => '12. Bagaimana cara mengajukan refund?', 'content' => 'Jika anda ingin membatalkan pesanan yang telah dibayar, atau produk yang Anda terima tidak sesuai dengan pesanan atau mengalami kerusakan, Anda dapat mengajukan refund dengan menghubungi layanan pelanggan kami. Proses pengembalian dana akan dilakukan dalam waktu 3-5 hari kerja setelah permintaan Anda disetujui.'],
        ['id' => 14, 'title' => '13. Bagaimana cara menghubungi layanan pelanggan?', 'content' => 'Anda dapat menghubungi nomor telepon kami di [nomor telepon]. Layanan pelanggan kami siap membantu Anda dari Senin hingga Sabtu, pukul 08:00 - 21:00 WIB.'],
        ['id' => 15, 'title' => '14. Bagaimana cara mendapatkan promo atau diskon?', 'content' => 'Kami juga menyediakan promo atau diskon yang dapat digunakan pada pembelian tertentu, yang akan diinformasikan melalui email setelah anda membuat akun.'],
    ];
@endphp

@extends('layouts.app')

@section('title', 'FAQ')

@section('content')
    <section class="p-4 md:p-6">
        <h1 class="mb-4 text-black">Pertanyaan yang Sering Diajukan</h1>
        <div class="flex flex-col-reverse justify-between gap-6 md:flex-row">
            <div class="h-full w-full text-center md:sticky md:top-20 md:w-1/2">
                <figure class="mb-6 hidden md:flex md:h-full md:justify-center">
                    <img
                        src="https://placehold.co/400"
                        class="size-72 object-cover"
                        alt="Gambar ilustrasi keranjang kosong"
                    />
                </figure>
                <p
                    class="mx-auto mb-6 w-full max-w-sm text-pretty text-sm font-medium tracking-tight text-black lg:text-base"
                >
                    Tidak menemukan pertanyaan yang anda cari? Anda dapat bertanya kepada kami melalui:
                </p>
                <x-common.button href="https://google.com" variant="secondary">
                    <svg
                        class="size-5 shrink-0 text-[#25D366]"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        x="0px"
                        y="0px"
                        viewBox="0 0 50 50"
                    >
                        <path
                            d="M25,2C12.318,2,2,12.318,2,25c0,3.96,1.023,7.854,2.963,11.29L2.037,46.73c-0.096,0.343-0.003,0.711,0.245,0.966 C2.473,47.893,2.733,48,3,48c0.08,0,0.161-0.01,0.24-0.029l10.896-2.699C17.463,47.058,21.21,48,25,48c12.682,0,23-10.318,23-23 S37.682,2,25,2z M36.57,33.116c-0.492,1.362-2.852,2.605-3.986,2.772c-1.018,0.149-2.306,0.213-3.72-0.231 c-0.857-0.27-1.957-0.628-3.366-1.229c-5.923-2.526-9.791-8.415-10.087-8.804C15.116,25.235,13,22.463,13,19.594 s1.525-4.28,2.067-4.864c0.542-0.584,1.181-0.73,1.575-0.73s0.787,0.005,1.132,0.021c0.363,0.018,0.85-0.137,1.329,1.001 c0.492,1.168,1.673,4.037,1.819,4.33c0.148,0.292,0.246,0.633,0.05,1.022c-0.196,0.389-0.294,0.632-0.59,0.973 s-0.62,0.76-0.886,1.022c-0.296,0.291-0.603,0.606-0.259,1.19c0.344,0.584,1.529,2.493,3.285,4.039 c2.255,1.986,4.158,2.602,4.748,2.894c0.59,0.292,0.935,0.243,1.279-0.146c0.344-0.39,1.476-1.703,1.869-2.286 s0.787-0.487,1.329-0.292c0.542,0.194,3.445,1.604,4.035,1.896c0.59,0.292,0.984,0.438,1.132,0.681 C37.062,30.587,37.062,31.755,36.57,33.116z"
                        />
                    </svg>
                    WhatsApp
                </x-common.button>
            </div>
            <div x-data="faqAccordion(@js($faqItems))" class="w-full divide-y divide-neutral-300 md:w-1/2">
                <div class="relative mb-4">
                    <div class="pointer-events-none absolute inset-y-0 start-4 flex items-center">
                        <svg
                            class="size-5 shrink-0 text-neutral-600"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <x-form.input
                        x-model="search"
                        class="w-full ps-12 text-black"
                        type="text"
                        name="search-question"
                        id="search-question"
                        placeholder="Apa yang ingin anda cari?"
                        autocomplete="off"
                    />
                </div>
                <template x-for="item in items" :key="item.id">
                    <div
                        x-show="
                            item.title.toLowerCase().includes(search.toLowerCase()) ||
                                item.content.toLowerCase().includes(search.toLowerCase())
                        "
                    >
                        <x-common.accordion>
                            <x-slot name="title">
                                <h3 class="text-lg tracking-tight text-black lg:text-xl" x-text="item.title"></h3>
                            </x-slot>
                            <p
                                class="mb-4 text-pretty text-sm font-medium tracking-tight text-black lg:text-base"
                                x-text="item.content"
                            ></p>
                        </x-common.accordion>
                    </div>
                </template>
                <p
                    x-show="filteredItems.length === 0"
                    class="text-pretty py-4 text-center text-sm font-medium tracking-tight text-black lg:text-base"
                >
                    Pertanyaan yang anda cari tidak ditemukan.
                </p>
            </div>
        </div>
    </section>
@endsection
