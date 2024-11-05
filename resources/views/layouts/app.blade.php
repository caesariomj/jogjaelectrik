@extends('layouts.base')

@section('body')
    <livewire:layout.navigation />

    <main>
        @yield('content', $slot ?? '')
    </main>
@endsection
