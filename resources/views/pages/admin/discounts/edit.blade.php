@extends('layouts.admin')

@section('title', 'Ubah Diskon ' . ucwords(str_replace('-', ' ', $discount->code)))

@section('content')
    <section>
        <h1 class="mb-4 text-black">Ubah Diskon &mdash; {{ ucwords(str_replace('-', ' ', $discount->code)) }}</h1>
        <livewire:admin.discounts.discount-edit-form :discount="$discount" />
    </section>
@endsection
