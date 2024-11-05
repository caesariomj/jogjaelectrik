@extends('layouts.base')

@section('body')
    <livewire:layout.navigation />

    <main>
        @yield('content')
    </main>
@endsection
