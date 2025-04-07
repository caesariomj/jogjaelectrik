@extends('layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
    <section class="container mx-auto flex max-w-md flex-row gap-6 p-6 md:max-w-[96rem] md:p-12">
        <x-user.sidebar />
        <section class="w-full shrink md:w-[calc(100%-18rem)]">
            <h1 class="mb-6 text-black">Riwayat Transaksi</h1>
            <livewire:user.transaction-item-list lazy />
        </section>
    </section>
@endsection
