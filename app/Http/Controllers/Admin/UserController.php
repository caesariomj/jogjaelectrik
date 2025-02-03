<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the user.
     */
    public function index(): View|RedirectResponse
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
        $user = (new User)->newFromBuilder(
            User::queryUserById(id: $id)->first()
        );

        if (! $user) {
            session()->flash('error', 'Pelanggan tidak ditemukan.');

            return redirect()->route('admin.users.index');
        }

        $user->phone_number = $user->phone_number ? Crypt::decryptString($user->phone_number) : null;
        $user->address = $user->address ? Crypt::decryptString($user->address) : null;
        $user->postal_code = $user->postal_code ? Crypt::decryptString($user->postal_code) : null;

        try {
            $this->authorize('view', $user);

            return view('pages.admin.users.show', compact('user'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.users.index');
        }
    }
}
