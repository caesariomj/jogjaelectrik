@extends('layouts.admin')

@section('title', 'Profil Saya')

@section('content')
    <section>
        <div class="mb-4 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <h1 class="leading-none text-black">Profil Saya</h1>
        </div>
        <livewire:admin.profile.update-profile-information-form lazy />
    </section>
@endsection
