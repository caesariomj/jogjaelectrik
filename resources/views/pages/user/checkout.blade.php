@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <section class="container mx-auto max-w-md p-6 md:max-w-[96rem] md:p-12">
        <h1 class="mb-6 text-black">Checkout</h1>
        <livewire:user.checkout-form :cart="$cart" />
    </section>
@endsection
