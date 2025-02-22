@extends('layouts.base')

@section('body')
    <div
        x-data="{
            hasSession:
                {{ session()->has('success') || session()->has('error') ? 'true' : 'false' }},
        }"
        x-init="setTimeout(() => (hasSession = false), 3000)"
        class="sticky top-0 z-10"
    >
        <x-common.alert />

        <livewire:layout.user.navigation />
    </div>

    <x-common.breadcrumb class="container mx-auto max-w-md px-6 pt-6 md:max-w-[96rem] md:px-12" />

    <main>
        @yield('content', $slot ?? '')
    </main>

    <x-user.footer class="container mx-auto max-w-md px-6 py-12 md:max-w-[96rem] md:px-12 md:py-24" />
@endsection
