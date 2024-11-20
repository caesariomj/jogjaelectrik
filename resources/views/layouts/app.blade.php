@extends('layouts.base')

@section('body')
    <livewire:layout.user.navigation />

    <x-common.alert />

    <x-common.breadcrumb class="px-4 pt-6 md:px-6" />

    <main>
        @yield('content', $slot ?? '')
    </main>
@endsection
