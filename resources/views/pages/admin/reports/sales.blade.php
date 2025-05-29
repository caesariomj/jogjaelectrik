@extends('layouts.admin')

@section('title', 'Penjualan')

@section('content')
    <section>
        <div class="mb-4 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <h1 class="leading-none text-black">Penjualan</h1>
        </div>
        <livewire:admin.reports.sales-table lazy />
    </section>
@endsection
