<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the user.
     */
    public function index(): View
    {
        try {
            $this->authorize('viewAny', User::class);

            return view('pages.admin.users.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Display the specified user.
     */
    public function show(string $id): View|RedirectResponse
    {
        $user = User::with(['city', 'city.province'])->withCount('orders')->find($id);

        if (! $user) {
            session()->flash('error', 'Pelanggan tidak ditemukan.');

            return redirect()->route('admin.users.index');
        }

        try {
            $this->authorize('view', $user);

            return view('pages.admin.users.show', compact('user'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.users.index');
        }
    }
}
