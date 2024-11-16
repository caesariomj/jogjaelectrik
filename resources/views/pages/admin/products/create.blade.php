@extends('layouts.admin')

@section('title', 'Tambah Produk')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Tambah Produk</h1>
        <livewire:admin.products.product-create-form />
    </section>
@endsection
