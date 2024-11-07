@extends('layouts.base')

@section('body')
    <livewire:layout.user.navigation />

    <main>
        @yield('content', $slot ?? '')
    </main>
@endsection
