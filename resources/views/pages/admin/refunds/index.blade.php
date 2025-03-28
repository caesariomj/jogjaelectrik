@extends('layouts.admin')

@section('title', 'Permintaan Refund')

@section('content')
    <section>
        <h1 class="mb-4 text-black">Permintaan Refund</h1>
        <livewire:admin.refunds.refund-table lazy />
    </section>
@endsection
