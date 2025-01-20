@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
    <section class="flex flex-row gap-6 p-4 md:p-6">
        <x-user.sidebar />
        <section class="w-full shrink">
            <h1 class="mb-4 text-black">Pesanan Saya</h1>
            <livewire:user.order-item-list />
        </section>
    </section>
@endsection
