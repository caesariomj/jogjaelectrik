@extends('layouts.admin')

@section('title', 'Detail Pelanggan ' . ucwords($user->name))

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
            <h1 class="leading-none text-black">Detail Pelanggan &mdash; {{ ucwords($user->name) }}</h1>
        </header>
        <dl class="mb-8 grid grid-cols-1">
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ ucwords($user->name) }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Email</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $user->email }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Handphone</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $user->phone_number ? '+62-' . $user->phone_number : '-' }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Alamat Lengkap</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    @php
                        $addressParts = [];

                        if ($user->address) {
                            $addressParts[] = $user->address;
                        }

                        if ($user->city_name) {
                            $addressParts[] = $user->city_name;
                        }

                        if ($user->province_name) {
                            $addressParts[] = $user->province_name;
                        }

                        if ($user->postal_code) {
                            $addressParts[] = $user->postal_code;
                        }

                        $address = implode(', ', $addressParts);
                    @endphp

                    {{ $address !== '' ? $address : '-' }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Pesanan</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $user->total_orders }}</dd>
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
        <div class="flex flex-col items-center gap-4 md:flex-row md:justify-end">
            <x-common.button
                :href="route('admin.users.index')"
                variant="secondary"
                class="w-full md:w-fit"
                wire:navigate
            >
                Kembali
            </x-common.button>
        </div>
    </section>
@endsection
