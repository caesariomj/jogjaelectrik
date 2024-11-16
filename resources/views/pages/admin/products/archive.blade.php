@extends('layouts.admin')

@section('title', 'Manajemen Arsip Produk')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Manajemen Arsip Produk</h1>
        <livewire:admin.products.product-table archived />
    </section>
@endsection
