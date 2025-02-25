<?php

use App\Models\Cart;
use App\Models\Discount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public ?Discount $discount = null;

    public function mount(Discount $discount)
    {
        $this->discount = $discount;
    }

    /**
     * Apply discount.
     *
     * @return  redirect if the user is not logged in.
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to update the cart.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function applyDiscount()
    {
        if (! auth()->check()) {
            return $this->redirectIntended(route('login'), navigate: true);
        }

        if (
            auth()
                ->user()
                ->roles->first()->name !== 'user'
        ) {
            session()->flash('error', 'Admin tidak dapat menggunakan diskon.');

            return $this->redirectIntended(route('home'), navigate: true);
        }

        $cart = Cart::firstOrCreate(['user_id' => auth()->id()]);

        try {
            $this->authorize('update', $cart);

            $cart->update([
                'discount_id' => $this->discount->id,
            ]);

            session()->flash('success', 'Diskon ' . $this->discount->name . ' berhasil diterapkan.');
            return $this->redirectIntended(route('home'), navigate: true);
        } catch (AuthorizationException $e) {
            Log::error('Database query error occurred', [
                'error_type' => 'QueryException',
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Using discount data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menerapkan diskon ' .
                    $this->discount->name .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('home'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Using discount data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('home'), navigate: true);
        }
    }
}; ?>

<div
    x-data="{
        now: null,
        endDate: '{{ $discount->end_date }}',
        timeRemaining: {
            hours: 0,
            minutes: 0,
            seconds: 0,
        },

        init() {
            this.now = new Date()
            const endDate = this.endDate != null ? new Date(this.endDate) : null

            setInterval(() => {
                this.now = new Date()
                const difference = endDate - this.now

                if (difference > 0) {
                    const hours = Math.floor(difference / (1000 * 60 * 60))
                    const minutes = Math.floor(
                        (difference % (1000 * 60 * 60)) / (1000 * 60),
                    )
                    const seconds = Math.floor((difference % (1000 * 60)) / 1000)

                    this.timeRemaining.hours = hours
                    this.timeRemaining.minutes = String(minutes).padStart(2, '0')
                    this.timeRemaining.seconds = String(seconds).padStart(2, '0')
                } else {
                    this.timeRemaining = { hours: 0, minutes: 0, seconds: 0 }
                }
            }, 1000)
        },
    }"
    class="relative h-full w-full shrink-0 overflow-hidden rounded-xl bg-gradient-to-r from-primary to-rose-700 lg:h-[36rem] lg:w-1/3"
>
    <div class="absolute -start-8 top-1/2 z-[1] size-16 -translate-y-1/2 rounded-full bg-white"></div>
    <div class="flex h-full w-full flex-col items-center justify-center p-8 lg:p-16">
        <h2 class="mb-4 leading-none text-white">Penawaran Terbatas</h2>

        @if ($discount->end_date)
            <p class="mb-2 text-center text-base font-medium leading-none tracking-tight text-white">
                Dapatkan sebelum:
            </p>
            <div
                class="mb-4 inline-flex w-52 items-center justify-center gap-x-2 rounded-full bg-primary-50/20 px-4 py-1"
            >
                <svg
                    class="size-5 shrink-0 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <line x1="10" x2="14" y1="2" y2="2" />
                    <line x1="12" x2="15" y1="14" y2="11" />
                    <circle cx="12" cy="14" r="8" />
                </svg>
                <p class="text-xl font-semibold text-white">
                    <span x-text="timeRemaining.hours"></span>
                    :
                    <span x-text="timeRemaining.minutes"></span>
                    :
                    <span x-text="timeRemaining.seconds"></span>
                </p>
            </div>
        @endif

        <p class="mb-8 text-pretty text-center leading-none text-white/80">
            Gunakan kode diskon
            <strong class="text-white">{{ $discount->code }}</strong>
            untuk menghemat
            <strong class="text-white">
                @if ($discount->type === 'percentage')
                    {{ formatPrice($discount->value) . '% !' }} (Maksimal Rp
                    {{ formatPrice($discount->max_discount_amount) }})
                @else
                    Rp {{ formatPrice($discount->value) }}
                @endif
            </strong>
            untuk berbagai produk yang hanya tersedia dalam waktu terbatas.
        </p>
        @auth
            <x-common.button type="button" variant="secondary" wire:click="applyDiscount">
                <svg
                    class="size-5 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="8" cy="21" r="1" />
                    <circle cx="19" cy="21" r="1" />
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                </svg>
                Gunakan Penawaran
            </x-common.button>
        @else
            <x-common.button variant="secondary" :href="route('login')" wire:navigate>
                <svg
                    class="size-5 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="8" cy="21" r="1" />
                    <circle cx="19" cy="21" r="1" />
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />
                </svg>
                Gunakan Penawaran
            </x-common.button>
        @endauth
    </div>
    <div class="absolute -end-8 top-1/2 z-[1] size-16 -translate-y-1/2 rounded-full bg-white"></div>
</div>
