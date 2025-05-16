@extends('layouts.base')

@section('body')
    <div
        x-data="{
            hasSession:
                {{ session()->has('success') || session()->has('error') ? 'true' : 'false' }},
            isOpen: false,
            toggleSidebar() {
                this.isOpen = ! this.isOpen

                if (this.isOpen) {
                    document.body.style.overflow = 'hidden'
                    document.querySelector('main').style.overflow = 'hidden'
                    document.getElementById('sidebar-nav').style.overflowY = 'auto'
                } else {
                    document.body.style.overflow = ''
                    document.querySelector('main').style.overflow = ''
                    document.getElementById('sidebar-nav').style.overflow = ''
                }
            },
        }"
        x-init="setTimeout(() => (hasSession = false), 3000)"
        class="bg-neutral-50"
    >
        <div class="sticky top-0 z-[3]">
            <x-common.alert />
        </div>

        <x-admin.sidebar />

        <div class="flex min-h-screen w-full flex-col lg:pl-64">
            <livewire:layout.admin.navigation />

            <x-common.breadcrumb class="p-4 md:p-6" />

            <main class="flex-1 px-4 md:px-6">
                @yield('content', $slot ?? '')
            </main>

            <x-admin.footer class="mt-auto p-4 md:p-6" />
        </div>
    </div>
@endsection
