@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <section>
        <h1 class="mb-3 text-black">Dashboard</h1>
        <section class="mx-auto mb-6 w-full">
            <h2 class="mb-3 !text-xl text-black md:!text-2xl">Ringkasan</h2>
            <livewire:admin.dashboard.summary-statistics />
        </section>
        <section class="mx-auto mb-6 flex h-[40rem] w-full items-center lg:h-96">
            <div class="h-full w-full rounded-xl bg-white p-4 shadow">
                <h2 class="!text-xl text-black md:!text-2xl">Statistik Penjualan Mingguan</h2>
                <p class="mt-1 text-sm font-medium tracking-tight text-black/70">
                    Grafik penjualan setiap hari selama seminggu terakhir
                </p>
                <div class="mt-2 h-[calc(100%-4rem)] w-full">
                    <livewire:admin.dashboard.weekly-order-chart />
                </div>
            </div>
        </section>
        <section class="mx-auto mb-6 h-full w-full rounded-xl bg-white p-4 shadow">
            <h2 class="!text-xl text-black md:!text-2xl">Pesanan</h2>
            <p class="mt-1 text-sm font-medium tracking-tight text-black/70">
                Tinjauan cepat status pesanan yang perlu diperhatikan
            </p>
            <div class="mt-4">
                <livewire:admin.dashboard.order-overview />
            </div>
        </section>
        <section class="mx-auto mb-6 flex h-full max-h-[64rem] w-full flex-col gap-6 xl:max-h-[32rem] xl:flex-row">
            <div class="h-full w-full rounded-xl bg-white p-4 shadow xl:w-1/2">
                <h2 class="!text-xl text-black md:!text-2xl">Diskon</h2>
                <p class="mt-1 text-sm font-medium tracking-tight text-black/70">
                    Daftar diskon aktif yang sedang berjalan di toko saat ini
                </p>
                <div class="mt-4">
                    <livewire:admin.dashboard.active-discount-table />
                </div>
            </div>
            <div class="h-full w-full rounded-xl bg-white p-4 shadow xl:w-1/2">
                <h2 class="!text-xl text-black md:!text-2xl">Stok Menipis</h2>
                <p class="mt-1 text-sm font-medium tracking-tight text-black/70">
                    Daftar produk dengan stok dibawah 10
                </p>
                <div class="mt-4">
                    <livewire:admin.dashboard.short-stock-product-table />
                </div>
            </div>
        </section>
        <section class="mx-auto mb-6 h-full max-h-[64rem] w-full rounded-xl bg-white p-4 shadow xl:max-h-[32rem]">
            <h2 class="!text-xl text-black md:!text-2xl">Ulasan Terbaru</h2>
            <p class="mt-1 text-sm font-medium tracking-tight text-black/70">Ulasan terbaru dari pelanggan</p>
            <div class="mt-4">
                <livewire:admin.dashboard.latest-review-table />
            </div>
        </section>
        <section class="mx-auto mb-6 h-full max-h-[64rem] w-full rounded-xl bg-white p-4 shadow xl:max-h-[32rem]">
            <h2 class="!text-xl text-black md:!text-2xl">Produk Terlaris</h2>
            <p class="mt-1 text-sm font-medium tracking-tight text-black/70">
                Produk dengan jumlah penjualan tertinggi
            </p>
            <div class="mt-4">
                <livewire:admin.dashboard.best-selling-product-table />
            </div>
        </section>
    </section>
@endsection
