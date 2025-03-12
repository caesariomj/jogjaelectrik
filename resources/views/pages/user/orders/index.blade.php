@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
    <section class="container mx-auto flex max-w-md flex-row gap-6 p-6 md:max-w-[96rem] md:p-12">
        <x-user.sidebar />
        <section class="w-full shrink">
            <h1 class="mb-6 text-black">Pesanan Saya</h1>
            <livewire:user.order-item-list lazy />
        </section>
    </section>
@endsection
