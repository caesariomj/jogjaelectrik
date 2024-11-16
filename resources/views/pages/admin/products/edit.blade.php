@extends('layouts.admin')

@section('title', 'Ubah Produk ' . ucwords($product->name))

@section('content')
    <section>
        <h1 class="mb-4 text-black">Ubah Produk &mdash; {{ ucwords($product->name) }}</h1>
        <livewire:admin.products.product-edit-form :product="$product" />
    </section>
@endsection
