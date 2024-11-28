@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <livewire:product-detail-section :product="$product" />
    @if (count($productRecommendations) > 0)
        <x-common.product-slider-section title="Rekomendasi Untuk Anda" :products="$productRecommendations" />
    @endif
@endsection
