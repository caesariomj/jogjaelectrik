<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', Refund::class);

            return view('pages.admin.refunds.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    public function show(string $id): View|RedirectResponse
    {
        $refund = (new Refund)->newFromBuilder(
            Refund::queryById(id: $id, columns: [
                'refunds.id',
                'refunds.payment_id',
                'refunds.xendit_refund_id',
                'refunds.status',
                'refunds.rejection_reason',
                'refunds.approved_at',
                'refunds.succeeded_at',
                'refunds.created_at',
                'refunds.updated_at',
            ])
        );

        if (! $refund) {
            session()->flash('error', 'Permintaan refund tidak ditemukan.');

            return redirect()->route('admin.refunds.index');
        }

        try {
            $this->authorize('view', $refund);

            return view('pages.admin.refunds.show', compact('refund'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.refunds.index');
        }
    }
}
