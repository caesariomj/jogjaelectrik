@extends('layouts.base')

@section('body')
    <livewire:layout.admin.navigation />

    <main>
        @yield('content', $slot ?? '')
    </main>
@endsection
