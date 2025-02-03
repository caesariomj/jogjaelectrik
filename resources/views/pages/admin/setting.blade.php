@extends('layouts.admin')

@section('title', 'Pengaturan Akun')

@section('content')
    <section>
        <div class="mb-4 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <h1 class="text-black">Pengaturan Akun</h1>
        </div>
        <livewire:admin.profile.update-password-form />
    </section>
@endsection
