@extends('layouts.admin')

@section('title', 'Tambah Diskon')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Tambah Diskon</h1>
        <livewire:admin.discounts.discount-create-form />
    </section>
@endsection
