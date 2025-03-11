@extends('layouts.app')

@section('title', 'Keranjang Belanja Saya')

@section('content')
    <section class="container mx-auto max-w-md px-6 py-6 md:max-w-[96rem] md:px-12">
        <h1 class="mb-6 text-black">Keranjang Belanja</h1>
        <livewire:user.cart-item-list :cart="$cart" />
    </section>
    @if (count($productRecommendations) > 0)
        <x-common.product-slider-section class="pb-12" :products="$productRecommendations">
            <x-slot name="header">
                <h2 class="text-pretty text-black">Produk Serupa</h2>
            </x-slot>
        </x-common.product-slider-section>
    @endif
@endsection
