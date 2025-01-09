<?php

use App\Models\Refund;
use App\Services\PaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public Refund $refund;

    protected PaymentService $paymentService;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $sortField = 'created_at';
    public string $sortDirection = 'asc';

    public string $rejectionReason = '';
    public string $otherRejectionReason = '';

    public function boot(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    #[Computed]
    public function refunds()
    {
        $refunds = Refund::query()
            ->with(['payment.order'])
            ->when($this->search !== '', function ($query) {
                return $query->whereHas('payment.order', function ($query) {
                    $query->where('order_number', 'like', '%' . $this->search . '%');
                });
            });

        if ($this->sortField === 'order_number') {
            $refunds
                ->join('payments', 'refunds.payment_id', '=', 'payments.id')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->orderBy('orders.order_number', $this->sortDirection)
                ->select('refunds.*');
        } elseif ($this->sortField === 'total_amount') {
            $refunds
                ->join('payments', 'refunds.payment_id', '=', 'payments.id')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->orderBy('orders.total_amount', $this->sortDirection)
                ->select('refunds.*');
        } elseif ($this->sortField === 'method') {
            $refunds
                ->join('payments', 'refunds.payment_id', '=', 'payments.id')
                ->orderBy('payments.method', $this->sortDirection)
                ->select('refunds.*');
        } elseif (in_array($this->sortField, ['status', 'created_at'])) {
            $refunds->orderBy($this->sortField, $this->sortDirection);
        }

        return $refunds->paginate(10);
    }

    public function resetSearch()
    {
        $this->reset('search');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function processRefund(string $id)
    {
        $refund = Refund::find($id);

        if (! $refund) {
            session()->flash('error', 'Refund tidak ditemukan.');
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        }

        try {
            $this->authorize('process', $refund);

            DB::transaction(function () use ($refund) {
                $result = $this->paymentService->createRefund($refund->payment->order);

                if ($result['failure_code'] === null) {
                    $refund->update([
                        'xendit_refund_id' => $result['id'],
                        'status' => 'approved',
                        'approved_at' => now(),
                    ]);
                } else {
                    $refund->update([
                        'status' => 'failed',
                        'rejection_reason' => strtolower($result['failure_code']),
                    ]);
                }
            });

            session()->flash(
                'success',
                'Permintaan refund pada pesanan dengan nomor: ' .
                    $refund->payment->order->order_number .
                    ', berhasil diproses.',
            );
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database Error During Refund Processing', [
                'error_message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam memproses permintaan refund pada pesanan dengan nomor: ' .
                    $refund->payment->order->order_number .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected Refund Process Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        }
    }

    #[On('initiate-refund-rejection')]
    public function confirmRefundRejection(string $id)
    {
        $this->refund = Refund::find($id);

        if (! $this->refund) {
            session()->flash('error', 'Permintaan refund tidak dapat ditemukan.');
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        }

        $this->dispatch('open-modal', 'confirm-refund-rejection-' . $this->refund->id);
    }

    public function rejectRefund()
    {
        $validated = $this->validate(
            rules: [
                'rejectionReason' => 'required|string|max:255',
                'otherRejectionReason' => 'nullable|required_if:rejectionReason,alasan_lainnya|string|max:255',
            ],
            attributes: [
                'rejectionReason' => 'Alasan penolakan',
                'otherRejectionReason' => 'Alasan penolakan lainnya',
            ],
        );

        $refund = $this->refund;

        try {
            $this->authorize('reject', $refund);

            DB::transaction(function () use ($refund, $validated) {
                $rejectionReason = 'Ditolak oleh admin: ';
                if ($validated['otherRejectionReason'] !== '') {
                    $rejectionReason .= strtolower($validated['otherRejectionReason']);
                } else {
                    $rejectionReason .= strtolower(str_replace('_', ' ', $validated['rejectionReason']));
                }

                $refund->update([
                    'status' => 'rejected',
                    'rejection_reason' => $rejectionReason,
                ]);
            });

            session()->flash(
                'success',
                'Permintaan refund pada pesanan dengan nomor: ' .
                    $refund->payment->order->order_number .
                    ', berhasil ditolak.',
            );
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database Error During Refund Rejection', [
                'error_message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menolak permintaan refund pada pesanan dengan nomor: ' .
                    $refund->payment->order->order_number .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected Refund Rejection Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.refunds.index'), navigate: true);
        }
    }
}; ?>

<div>
    <div class="pointer-events-auto mb-4">
        <div
            class="rounded-md border border-yellow-400 bg-yellow-50 p-4 shadow-md"
            role="alert"
            aria-live="polite"
            aria-atomic="true"
        >
            <div class="flex items-center" role="presentation">
                <div class="flex-shrink-0" aria-hidden="true">
                    <svg class="size-4 text-yellow-800" viewBox="0 0 20 20" fill="currentColor">
                        <title>Ikon notifikasi informasi</title>
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                        />
                    </svg>
                </div>
                <p role="heading" aria-level="2" class="ml-3 text-sm tracking-tight text-yellow-800">
                    <strong>Perhatian!</strong>
                    Proses refund melalui sistem hanya mendukung metode pembayaran menggunakan
                    <strong>e-wallet</strong>
                    . Untuk metode pembayaran selain e-wallet, proses refund harus dilakukan secara
                    <strong>manual</strong>
                    , seperti melalui transfer bank kepada pengguna.
                    <a href="#" class="inline-flex items-center gap-x-1 underline">
                        Klik disini untuk mempelajari lebih lanjut
                        <svg
                            class="size-3 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25"
                            />
                        </svg>
                    </a>
                </p>
            </div>
        </div>
    </div>
    <div class="mb-6 flex flex-col gap-y-3">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 z-20 flex items-center ps-3.5">
                <svg
                    class="size-4 shrink-0 text-black/70"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </div>
            <div class="relative">
                <x-form.input
                    id="order-search"
                    name="order-search"
                    wire:model.live.debounce.250ms="search"
                    class="block w-full ps-10"
                    type="text"
                    role="combobox"
                    placeholder="Cari data refund berdasarkan nomor pesanan..."
                    autocomplete="off"
                />
                <div
                    wire:loading
                    wire:target="search, resetSearch"
                    class="pointer-events-none absolute end-0 top-1/2 -translate-y-1/2 pe-3"
                >
                    <svg
                        class="size-5 shrink-0 animate-spin text-black"
                        fill="currentColor"
                        viewBox="0 0 256 256"
                        aria-hidden="true"
                    >
                        <path
                            d="M232,128a104,104,0,0,1-208,0c0-41,23.81-78.36,60.66-95.27a8,8,0,0,1,6.68,14.54C60.15,61.59,40,93.27,40,128a88,88,0,0,0,176,0c0-34.73-20.15-66.41-51.34-80.73a8,8,0,0,1,6.68-14.54C208.19,49.64,232,87,232,128Z"
                        />
                    </svg>
                </div>
                @if ($search)
                    <button
                        wire:click="resetSearch"
                        wire:loading.remove
                        wire:target="search, resetSearch"
                        type="button"
                        class="absolute end-0 top-1/2 -translate-y-1/2 pe-3"
                    >
                        <svg
                            class="size-5 shrink-0 text-black"
                            fill="currentColor"
                            viewBox="0 0 256 256"
                            aria-hidden="true"
                        >
                            <path
                                d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"
                            />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
    <div class="relative w-full overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-neutral-300">
                <tr>
                    <th scope="col" class="p-4 text-sm font-semibold tracking-tight text-black" align="left">No.</th>
                    <th scope="col" align="left">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('order_number')"
                        >
                            Nomor Pesanan
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'order_number' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'order_number' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('total_amount')"
                        >
                            Total Belanja
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'total_amount' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'total_amount' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('method')"
                        >
                            Metode Pembayaran
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'method' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'method' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('status')"
                        >
                            Status
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'status' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'status' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('created_at')"
                        >
                            Diajukan Pada
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'created_at' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'created_at' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="p-4" align="right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-300">
                @forelse ($this->refunds as $refund)
                    <tr
                        wire:key="{{ $refund->id }}"
                        wire:loading.class="opacity-50"
                        wire:target="search,sortBy,resetSearch"
                    >
                        <td class="p-4 font-normal tracking-tight text-black/70" align="left">
                            {{ $loop->index + 1 . '.' }}
                        </td>
                        <td class="min-w-32 p-4" align="left">
                            <a
                                href="{{ route('admin.orders.show', ['orderNumber' => $refund->payment->order->order_number]) }}"
                                class="inline-flex items-center gap-x-1 font-medium tracking-tight text-black underline transition-colors hover:text-primary"
                                wire:navigate
                            >
                                {{ $refund->payment->order->order_number }}
                                <svg
                                    class="size-3 shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25"
                                    />
                                </svg>
                            </a>
                        </td>
                        <td class="min-w-40 p-4 font-normal tracking-tight text-black/70" align="center">
                            Rp {{ formatPrice($refund->payment->order->total_amount) }}
                        </td>
                        <td
                            class="inline-flex w-full min-w-40 items-center justify-center gap-x-2 p-4 font-normal tracking-tight text-black/70"
                            align="center"
                        >
                            @php
                                if (str_contains($refund->payment->method, 'bank_transfer_')) {
                                    $paymentType = 'Transfer Bank';
                                    $paymentMethod = str_replace('bank_transfer_', '', $refund->payment->method);
                                } elseif (str_contains($refund->payment->method, 'ewallet_')) {
                                    $paymentType = 'E-Wallet';
                                    $paymentMethod = str_replace('ewallet_', '', $refund->payment->method);
                                }
                            @endphp

                            <img
                                src="{{ asset('images/logos/payments/' . $paymentMethod . '.webp') }}"
                                alt="Logo {{ strtoupper($paymentMethod) }}"
                                class="h-auto w-10"
                                loading="lazy"
                            />
                            {{ ucwords($paymentType . ' - ' . $paymentMethod) }}
                        </td>
                        <td class="min-w-32 p-4" align="center">
                            @if ($refund->status === 'pending')
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium tracking-tight text-yellow-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-yellow-800"></span>
                                    Menunggu Diproses
                                </span>
                            @elseif (in_array($refund->status, ['approved', 'succeeded']))
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                                    {{ $refund->status === 'approved' ? 'Disetujui' : 'Berhasil' }}
                                </span>
                            @elseif (in_array($refund->status, ['failed', 'rejected']))
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-medium tracking-tight text-red-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-red-800"></span>
                                    {{ $refund->status === 'failed' ? 'Gagal' : 'Ditolak' }}
                                </span>
                            @endif
                        </td>
                        <td class="min-w-52 p-4 font-normal tracking-tight text-black/70" align="center">
                            {{ formatTimestamp($refund->created_at) }}
                        </td>
                        <td class="relative px-4 py-2" align="right">
                            <x-common.dropdown width="60">
                                <x-slot name="trigger">
                                    <button
                                        type="button"
                                        class="rounded-full p-2 text-black hover:bg-neutral-100 disabled:hover:bg-white"
                                        wire:loading.attr="disabled"
                                        wire:target="search,sortBy,resetSearch"
                                    >
                                        <svg
                                            class="size-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <circle cx="12" cy="12" r="1" />
                                            <circle cx="12" cy="5" r="1" />
                                            <circle cx="12" cy="19" r="1" />
                                        </svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @can('view refund details')
                                        <x-common.dropdown-link
                                            :href="route('admin.refunds.show', ['id' => $refund->id])"
                                            wire:navigate
                                        >
                                            <svg
                                                class="size-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="m3 10 2.5-2.5L3 5" />
                                                <path d="m3 19 2.5-2.5L3 14" />
                                                <path d="M10 6h11" />
                                                <path d="M10 12h11" />
                                                <path d="M10 18h11" />
                                            </svg>
                                            Detail
                                        </x-common.dropdown-link>
                                    @endcan

                                    @if ($refund->status !== 'pending')
                                        @can('process refunds')
                                            <x-common.dropdown-link
                                                class="!items-start text-teal-500 hover:!bg-teal-50"
                                                x-on:click.prevent="$dispatch('open-modal', 'process-refund-{{ $refund->id }}')"
                                            >
                                                <svg
                                                    class="mt-0.5 size-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <path d="M20 6 9 17l-5-5" />
                                                </svg>
                                                Proses Refund
                                            </x-common.dropdown-link>
                                            <template x-teleport="body">
                                                <x-common.modal
                                                    name="process-refund-{{ $refund->id }}"
                                                    :show="$errors->isNotEmpty()"
                                                    focusable
                                                >
                                                    <div class="flex flex-col items-center p-6">
                                                        <div
                                                            class="mb-4 rounded-full bg-red-100 p-4"
                                                            aria-hidden="true"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                fill="currentColor"
                                                                class="size-16 text-red-500"
                                                            >
                                                                <path
                                                                    fill-rule="evenodd"
                                                                    d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                                                    clip-rule="evenodd"
                                                                />
                                                            </svg>
                                                        </div>
                                                        <h2 class="mb-2 text-center text-black">
                                                            Proses Permintaan Refund
                                                        </h2>
                                                        <p
                                                            class="mb-8 text-center text-base font-medium tracking-tight text-black/70"
                                                        >
                                                            Apakah anda yakin ingin memproses permintaan refund pada
                                                            pesanan dengan nomor
                                                            <strong>
                                                                "{{ $refund->payment->order->order_number }}"
                                                            </strong>
                                                            ini ? Aksi ini akan mengembalikan seluruh total belanja
                                                            kepada pengguna terkait termasuk dengan potongan diskon dan
                                                            ongkos kirim.
                                                        </p>
                                                        <div class="flex w-full flex-col justify-end gap-4 md:flex-row">
                                                            <x-common.button
                                                                variant="secondary"
                                                                x-on:click="$dispatch('close')"
                                                                wire:loading.class="!pointers-event-none !cursor-not-allowed opacity-50"
                                                                wire:target="processRefund('{{ $refund->id }}')"
                                                            >
                                                                Batal
                                                            </x-common.button>
                                                            <x-common.button
                                                                wire:click="processRefund('{{ $refund->id }}')"
                                                                variant="primary"
                                                                wire:loading.attr="disabled"
                                                                wire:target="processRefund('{{ $refund->id }}')"
                                                            >
                                                                <span
                                                                    wire:loading.remove
                                                                    wire:target="processRefund('{{ $refund->id }}')"
                                                                >
                                                                    Proses Refund
                                                                </span>
                                                                <span
                                                                    wire:loading.flex
                                                                    wire:target="processRefund('{{ $refund->id }}')"
                                                                    class="items-center gap-x-2"
                                                                >
                                                                    <div
                                                                        class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                                                        role="status"
                                                                        aria-label="loading"
                                                                    >
                                                                        <span class="sr-only">Sedang diproses...</span>
                                                                    </div>
                                                                    Sedang diproses...
                                                                </span>
                                                            </x-common.button>
                                                        </div>
                                                    </div>
                                                </x-common.modal>
                                            </template>
                                        @endcan

                                        @can('reject refunds')
                                            <x-common.dropdown-link
                                                class="!items-start text-red-500 hover:!bg-red-50"
                                                x-on:click.prevent.stop="$dispatch('initiate-refund-rejection', { 'id': '{{ $refund->id }}' })"
                                                wire:loading.class="!opacity-50 !cursor-wait"
                                            >
                                                <svg
                                                    class="mt-0.5 size-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <circle cx="12" cy="12" r="10" />
                                                    <path d="m4.9 4.9 14.2 14.2" />
                                                </svg>
                                                Tolak Refund
                                            </x-common.dropdown-link>
                                            <template x-teleport="body">
                                                <x-common.modal
                                                    name="confirm-refund-rejection-{{ $refund->id }}"
                                                    :show="$errors->isNotEmpty()"
                                                    focusable
                                                >
                                                    <form
                                                        wire:submit="rejectRefund"
                                                        class="flex flex-col items-center p-6"
                                                    >
                                                        <div
                                                            class="mb-4 rounded-full bg-red-100 p-4"
                                                            aria-hidden="true"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                fill="currentColor"
                                                                class="size-16 text-red-500"
                                                            >
                                                                <path
                                                                    fill-rule="evenodd"
                                                                    d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                                                    clip-rule="evenodd"
                                                                />
                                                            </svg>
                                                        </div>
                                                        <h2 class="mb-2 text-center text-black">
                                                            Tolak Permintaan Refund
                                                        </h2>
                                                        <p
                                                            class="mb-4 text-center text-base font-medium tracking-tight text-black/70"
                                                        >
                                                            Apakah anda yakin ingin menolak permintaan refund pada
                                                            pesanan dengan nomor
                                                            <strong>
                                                                "{{ $refund->payment->order->order_number }}"
                                                            </strong>
                                                            ini ? Aksi ini tidak dapat dibatalkan, pastikan anda
                                                            memberikan alasan penolakan yang jelas.
                                                        </p>
                                                        <div
                                                            x-data="{
                                                                selectedReason: @entangle('rejectionReason'),
                                                            }"
                                                            class="flex w-full flex-col items-start"
                                                        >
                                                            <div class="flex w-full flex-col items-start">
                                                                <x-form.input-label
                                                                    value="Alasan Penolakan"
                                                                    for="reason"
                                                                />
                                                                <select
                                                                    x-model="selectedReason"
                                                                    name="rejection-reason"
                                                                    id="rejection-reason"
                                                                    class="mt-1 block w-full rounded-lg border-neutral-300 px-4 py-3 pe-9 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                                                    required
                                                                    x-on:change="$wire.set('rejectionReason', selectedReason)"
                                                                >
                                                                    <option value="" selected>
                                                                        Pilih Alasan Penolakan Permintaan Refund
                                                                    </option>
                                                                    <option value="permintaan_tidak_valid">
                                                                        Permintaan tidak valid
                                                                    </option>
                                                                    <option value="jumlah_melebihi_batas">
                                                                        Jumlah refund melebihi batas yang diizinkan
                                                                    </option>
                                                                    <option value="pesanan_tidak_memenuhi_syarat">
                                                                        Pesanan tidak memenuhi syarat untuk refund
                                                                    </option>
                                                                    <option value="aktivitas_penipuan">
                                                                        Diduga aktivitas penipuan
                                                                    </option>
                                                                    <option value="permintaan_duplikat">
                                                                        Permintaan refund duplikat
                                                                    </option>
                                                                    <option value="produk_sudah_dikembalikan">
                                                                        Produk sudah dikembalikan
                                                                    </option>
                                                                    <option value="kesalahan_pelanggan">
                                                                        Kesalahan dari pihak pelanggan (misal:
                                                                        memasukkan data yang salah)
                                                                    </option>
                                                                    <option value="melanggar_kebijakan_refund">
                                                                        Pelanggaran terhadap kebijakan refund
                                                                    </option>
                                                                    <option value="masalah_teknis">
                                                                        Masalah teknis dalam memproses refund
                                                                    </option>
                                                                    <option value="alasan_lainnya">
                                                                        Alasan lainnya
                                                                    </option>
                                                                </select>
                                                                <x-form.input-error
                                                                    :messages="$errors->get('rejectionReason')"
                                                                    class="mt-2"
                                                                />
                                                            </div>
                                                            <div
                                                                class="mt-4 w-full"
                                                                x-show="selectedReason === 'alasan_lainnya'"
                                                            >
                                                                <x-form.input-label
                                                                    value="Alasan Lainnya"
                                                                    for="other-reason"
                                                                />
                                                                <textarea
                                                                    wire:model.lazy="otherRejectionReason"
                                                                    name="other-reason"
                                                                    id="other-reason"
                                                                    class="mt-1 block w-full rounded-lg border-neutral-300 px-4 py-3 pe-9 text-sm focus:border-primary focus:ring-primary"
                                                                    placeholder="Masukkan alasan lainnya..."
                                                                    x-bind:required="selectedReason === 'alasan_lainnya'"
                                                                ></textarea>
                                                                <x-form.input-error
                                                                    :messages="$errors->get('otherRejectionReason')"
                                                                    class="mt-2"
                                                                />
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="mt-8 flex w-full flex-col justify-end gap-4 md:flex-row"
                                                        >
                                                            <x-common.button
                                                                variant="secondary"
                                                                x-on:click="$dispatch('close')"
                                                                wire:loading.class="!pointers-event-none !cursor-not-allowed opacity-50"
                                                                wire:target="rejectRefund"
                                                            >
                                                                Batal
                                                            </x-common.button>
                                                            <x-common.button
                                                                type="submit"
                                                                variant="danger"
                                                                wire:loading.attr="disabled"
                                                                wire:target="rejectRefund"
                                                            >
                                                                <span wire:loading.remove wire:target="rejectRefund">
                                                                    Tolak Permintaan Refund
                                                                </span>
                                                                <span
                                                                    wire:loading.flex
                                                                    wire:target="rejectRefund"
                                                                    class="items-center gap-x-2"
                                                                >
                                                                    <div
                                                                        class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                                                        role="status"
                                                                        aria-label="loading"
                                                                    >
                                                                        <span class="sr-only">Sedang diproses...</span>
                                                                    </div>
                                                                    Sedang diproses...
                                                                </span>
                                                            </x-common.button>
                                                        </div>
                                                    </form>
                                                </x-common.modal>
                                            </template>
                                        @endcan
                                    @endif
                                </x-slot>
                            </x-common.dropdown>
                        </td>
                    </tr>
                @empty
                    <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch">
                        <td class="p-4" colspan="9">
                            <figure class="my-4 flex h-full flex-col items-center justify-center">
                                <img
                                    src="https://placehold.co/400"
                                    class="mb-6 size-72 object-cover"
                                    alt="Gambar ilustrasi permintaan refund tidak ditemukan"
                                />
                                <figcaption class="flex flex-col items-center">
                                    <h2 class="mb-3 text-center !text-2xl text-black">
                                        Permintaan Refund Tidak Ditemukan
                                    </h2>
                                    <p class="text-center text-base font-normal tracking-tight text-black/70">
                                        @if ($search)
                                            Permintaan refund yang Anda cari tidak ditemukan, silakan coba untuk mengubah kata kunci
                                        pencarian Anda.
                                        @else
                                                Seluruh permintaan refund pelanggan akan ditampilkan di halaman ini.
                                        @endif
                                    </p>
                                </figcaption>
                            </figure>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div
            class="absolute left-1/2 top-16 h-full -translate-x-1/2"
            wire:loading
            wire:target="search,sortBy,resetSearch"
        >
            <div
                class="inline-block size-10 animate-spin rounded-full border-4 border-current border-t-transparent text-primary"
                role="status"
                aria-label="loading"
            >
                <span class="sr-only">Sedang diproses...</span>
            </div>
        </div>
    </div>
    {{ $this->refunds->links() }}
</div>
