<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('view', auth()->user());

            return view('pages.admin.profile');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    public function setting(): View|RedirectResponse
    {
        try {
            $this->authorize('view', auth()->user());

            return view('pages.admin.setting');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }
}
