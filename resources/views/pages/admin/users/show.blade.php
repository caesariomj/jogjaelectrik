@extends('layouts.admin')

@section('title', 'Detail Pelanggan ' . $user->name)

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.users.index')"
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
            <h1 class="leading-none text-black">Detail Pelanggan &mdash; {{ $user->name }}</h1>
        </header>
        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Informasi Utama Pelanggan</h2>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $user->name }}</dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Email</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $user->email }}</dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Handphone</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ $user->phone_number ? '0' . \Illuminate\Support\Facades\Crypt::decryptString($user->phone_number) : '-' }}
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Alamat Lengkap</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ $user->city_id && $user->address && $user->postal_code ? \Illuminate\Support\Facades\Crypt::decryptString($user->address) . ', ' . \Illuminate\Support\Facades\Crypt::decryptString($user->postal_code) . ' - ' . $user->city->name . ', ' . $user->city->province->name : '-' }}
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Pesanan</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $user->orders_count }}</dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Bergabung Pada</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ formatTimestamp($user->created_at) }}
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ formatTimestamp($user->updated_at) }}
                    </dd>
                </div>
            </dl>
        </section>
    </section>
@endsection
