@extends('layouts.admin')

@section('title', 'Manajemen Refund')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Manajemen Refund</h1>
        <livewire:admin.refunds.refund-table lazy />
    </section>
@endsection
