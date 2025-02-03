<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display a listing of the admin.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', User::class);

            return view('pages.admin.admins.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Show the form for creating a new admin.
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->authorize('create', User::class);

            return view('pages.admin.admins.create');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.admins.index');
        }
    }

    /**
     * Display the specified admin.
     */
    public function show(string $id): View|RedirectResponse
    {
        $admin = (new User)->newFromBuilder(
            User::queryAdminById(id: $id)->first()
        );

        if (! $admin) {
            session()->flash('error', 'Admin tidak ditemukan.');

            return redirect()->route('admin.admins.index');
        }

        try {
            $this->authorize('view', $admin);

            return view('pages.admin.admins.show', compact('admin'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.admins.index');
        }
    }

    /**
     * Show the form for editing the specified admin.
     */
    public function edit(string $id): View|RedirectResponse
    {
        $admin = (new User)->newFromBuilder(
            User::queryAdminById(id: $id, columns: ['users.id', 'users.name', 'users.email'])->first()
        );

        if (! $admin) {
            session()->flash('error', 'Admin tidak ditemukan.');

            return redirect()->route('admin.admins.index');
        }

        try {
            $this->authorize('update', $admin);

            return view('pages.admin.admins.edit', compact('admin'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.admins.index');
        }
    }
}
