@extends('layouts.app')

@section('title', $subcategory ? ucwords(str_replace('-', ' ', $subcategory)) : ($category ? ucwords(str_replace('-', ' ', $category)) : 'Produk'))

@section('content')
    <section class="container mx-auto max-w-md px-6 pb-12 pt-6 md:max-w-[96rem] md:px-12">
        <h1 class="mb-6 text-black">
            Produk
            {{ $subcategory ? ucwords(str_replace('-', ' ', $subcategory)) : ($category ? ucwords(str_replace('-', ' ', $category)) : '') }}
        </h1>
        <livewire:product-catalog :category="$category" :subcategory="$subcategory" :search="$search" />
    </section>
@endsection
