@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <section class="p-4 md:p-6">
        <h1 class="mb-4 text-black">Checkout</h1>
        <livewire:user.checkout-form :cart="$cart" />
    </section>
@endsection
