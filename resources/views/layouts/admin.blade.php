@extends('layouts.base')

@section('body')
    <div
        x-data="{
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
    >
        <x-common.alert />

        <x-admin.sidebar />

        <div class="h-full w-full lg:pl-64">
            <livewire:layout.admin.navigation />

            <x-common.breadcrumb class="px-4 pt-4 md:px-6" />

            <main class="flex-1 px-4 pt-6 md:px-6">
                @yield('content', $slot ?? '')
            </main>
        </div>
    </div>
@endsection
