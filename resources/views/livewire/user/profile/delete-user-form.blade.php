<?php

use App\Livewire\Actions\Logout;
use App\Livewire\Forms\DeleteUserForm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Volt\Component;

new class extends Component {
    public DeleteUserForm $form;

    public function mount(): void
    {
        $this->form->setUser(auth()->user());
    }

    /**
     * Lazy loading that displays the user update profile skeleton.
     */
    public function placeholder(): View
    {
        return view('components.skeleton.user-delete-account');
    }

    /**
     * Delete user account.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to delete the user account.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function deleteUser(Logout $logout)
    {
        $this->form->validate();

        try {
            $this->authorize('delete', $this->form->user);

            tap(Auth::user(), $logout(...))->delete();

            session()->flash('success', 'Akun anda berhasil dihapus.');
            return $this->redirect('/', navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('setting'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'User removing their account',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('setting'), navigate: true);
        }
    }
}; ?>

<div>
    <section class="space-y-6">
        <header class="flex w-full flex-col pb-4">
            <h2 class="mb-2 text-xl text-black">Hapus Akun</h2>
            <p class="text-base tracking-tight text-black/70">
                Setelah akun Anda dihapus, semua data pesanan dan pembayaran Anda akan dihapus secara permanen dari
                sistem.
            </p>
        </header>
        <x-common.button
            variant="danger"
            class="w-full md:float-end md:w-fit"
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >
            Hapus Akun
        </x-common.button>
        <x-common.modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
            <form wire:submit="deleteUser" class="flex flex-col items-center p-6">
                <div class="mb-4 rounded-full bg-red-100 p-4" aria-hidden="true">
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
                <h2 class="mb-2 text-center text-black">Hapus Akun</h2>
                <p class="mb-4 text-center text-base font-medium tracking-tight text-black/70">
                    Apakah anda yakin ingin menghapus akun Anda? Setelah akun Anda dihapus, semua sumber daya dan
                    datanya akan dihapus secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin
                    menghapus akun Anda secara permanen.
                </p>
                <div class="mb-8 flex w-full flex-col items-start">
                    <x-form.input-label for="password" value="Password" class="sr-only" />
                    <x-form.input
                        wire:model.lazy="form.password"
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-full"
                        placeholder="Password..."
                        :hasError="$errors->has('form.password')"
                    />
                    <x-form.input-error :messages="$errors->get('form.password')" class="mt-2" />
                </div>

                <div class="flex w-full flex-col justify-end gap-4 md:flex-row">
                    <x-common.button
                        type="button"
                        variant="secondary"
                        class="w-full md:w-fit"
                        x-on:click="$dispatch('close')"
                        wire:loading.class="opacity-50 !pointers-event-none !cursor-not-allowed hover:!bg-neutral-100"
                        wire:target="deleteUser"
                    >
                        Batal
                    </x-common.button>
                    <x-common.button variant="danger" type="submit">
                        <span wire:loading.remove wire:target="deleteUser">Hapus Akun</span>
                        <div
                            wire:loading
                            wire:target="deleteUser"
                            class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                            role="status"
                            aria-label="loading"
                        >
                            <span class="sr-only">Sedang diproses...</span>
                        </div>
                        <span wire:loading wire:target="deleteUser">Sedang diproses...</span>
                    </x-common.button>
                </div>
            </form>
        </x-common.modal>
    </section>
</div>
