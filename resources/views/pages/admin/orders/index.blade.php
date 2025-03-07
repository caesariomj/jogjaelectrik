@extends('layouts.admin')

@section('title', 'Manajemen Pesanan')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Manajemen Pesanan</h1>
        <livewire:admin.orders.order-table lazy />
    </section>
@endsection
