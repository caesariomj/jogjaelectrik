@extends('layouts.app')

@section('title', 'Pengaturan Akun')

@section('content')
    <section class="container mx-auto flex max-w-md flex-row gap-6 p-6 md:max-w-[96rem] md:p-12">
        <x-user.sidebar />
        <section class="w-full shrink">
            <h1 class="mb-6 text-black">Pengaturan Akun</h1>
            <livewire:user.profile.update-password-form lazy />
            <hr class="my-8 border-neutral-300" />
            <livewire:user.profile.delete-user-form lazy />
        </section>
    </section>
@endsection
