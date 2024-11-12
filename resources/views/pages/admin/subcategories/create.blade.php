@extends('layouts.admin')

@section('title', 'Tambah Subkategori')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Tambah Subkategori</h1>
        <livewire:admin.subcategories.subcategory-create-form />
    </section>
@endsection
