@extends('layouts.admin')

@section('title', 'Ubah Kategori ' . ucwords($category->name))

@section('content')
    <section>
        <h1 class="mb-4 text-black">Ubah Kategori &mdash; {{ ucwords($category->name) }}</h1>
        <livewire:admin.categories.category-edit-form :category="$category" />
    </section>
@endsection
