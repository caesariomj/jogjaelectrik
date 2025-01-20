@extends('layouts.app')

@section('title', 'Pengaturan Akun')

@section('content')
    <section class="flex flex-row gap-6 p-4 md:p-6">
        <x-user.sidebar />
        <section class="w-full shrink">
            <h1 class="mb-4 text-black">Pengaturan Akun</h1>
            <livewire:user.profile.update-password-form />
            <hr class="my-8 border-neutral-300" />
            <livewire:user.profile.delete-user-form />
        </section>
    </section>
@endsection
