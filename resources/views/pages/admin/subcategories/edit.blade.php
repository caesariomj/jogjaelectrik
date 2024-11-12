@extends('layouts.admin')

@section('title', 'Ubah Subkategori ' . ucwords($subcategory->name))

@section('content')
    <section>
        <h1 class="mb-4 text-black">Ubah Subkategori &mdash; {{ ucwords($subcategory->name) }}</h1>
        <livewire:admin.subcategories.subcategory-edit-form :subcategory="$subcategory" />
    </section>
@endsection
