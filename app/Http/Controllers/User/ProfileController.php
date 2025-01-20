<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('view', auth()->user());

            return view('pages.user.profile');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('home');
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred when the user tried to access his profile page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return redirect()->route('home');
        }
    }

    public function setting(): View|RedirectResponse
    {
        try {
            $this->authorize('view', auth()->user());

            return view('pages.user.setting');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('home');
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred when the user tried to access his account setting page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return redirect()->route('home');
        }
    }
}
