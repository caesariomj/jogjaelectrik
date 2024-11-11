@extends('layouts.admin')

@section('title', 'Tambah Kategori')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Tambah Kategori</h1>
        <livewire:admin.categories.category-create-form />
    </section>
@endsection
