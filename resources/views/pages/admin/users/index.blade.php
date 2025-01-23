@extends('layouts.admin')

@section('title', 'Manajemen Pelanggan')

@section('content')
    <section>
        <div class="mb-4 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
            <h1 class="leading-none text-black">Manajemen Pelanggan</h1>
        </div>
        <livewire:admin.users.user-table />
    </section>
@endsection
