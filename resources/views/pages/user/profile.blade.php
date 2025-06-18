@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <section class="container mx-auto flex max-w-md flex-row gap-6 px-6 py-6 md:max-w-[96rem] md:px-12">
        <x-user.sidebar />
        <section class="w-full shrink">
            <h1 class="mb-6 text-black">Profil Saya</h1>
            <livewire:user.profile.update-profile-information-form lazy />
        </section>
    </section>
@endsection
