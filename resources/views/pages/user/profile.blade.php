@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <section class="flex flex-row gap-6 p-4 md:p-6">
        <x-user.sidebar />
        <section class="w-full shrink">
            <h1 class="mb-4 text-black">Profil Saya</h1>
            <livewire:user.profile.update-profile-information-form />
        </section>
    </section>
@endsection
