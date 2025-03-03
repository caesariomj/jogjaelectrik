@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <livewire:product-detail-section :product="$product" />
    @if ($productRecommendations->count() > 0)
        <x-common.product-slider-section :products="$productRecommendations">
            <x-slot name="header">
                <h2 class="text-pretty text-black">Rekomendasi Untuk Anda</h2>
            </x-slot>
        </x-common.product-slider-section>
    @endif
@endsection
