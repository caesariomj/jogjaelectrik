@extends('layouts.app')

@section('title', 'Keranjang Belanja Saya')

@section('content')
    <section class="p-4 md:p-6">
        <h1 class="mb-4 text-black">Keranjang Belanja Saya</h1>
        <livewire:user.cart-item-list context="cart-page" />
    </section>
    @if (count($productRecommendations) > 0)
        <x-common.product-slider-section title="Rekomendasi Untuk Anda" :products="$productRecommendations" />
    @endif
@endsection
