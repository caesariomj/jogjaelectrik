<?php

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithoutUrlPagination;

    #[Url(as: 'pencarian', except: '')]
    public string $search = '';

    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public int $perPage = 10;

    /**
     * Lazy loading that displays the table skeleton with dynamic table rows.
     */
    public function placeholder(): View
    {
        $totalRows = 8;

        return view('components.skeleton.table', compact('totalRows'));
    }

    /**
     * Get a paginated list of users with total order counts.
     */
    #[Computed]
    public function users()
    {
        return User::queryAllUsers()
            ->when($this->search !== '', function ($query) {
                return $query->where('users.name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage)
            ->through(function ($user) {
                $user->phone_number = $user->phone_number ? Crypt::decryptString($user->phone_number) : null;
                return $user;
            });
    }

    /**
     * Reset the search query.
     */
    public function resetSearch()
    {
        $this->reset('search');
    }

    /**
     * Sort the users by the specified field.
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    /**
     * Delete a user from the system.
     *
     * @param   string  $id - The ID of the user to delete.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to delete the user.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function delete(string $id)
    {
        $user = (new User())->newFromBuilder(User::queryById($id)->first());

        if (! $user) {
            session()->flash('error', 'Pelanggan tidak ditemukan.');
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        }

        $userName = $user->name;

        try {
            $this->authorize('delete', $user);

            DB::transaction(function () use ($user) {
                $user->delete();
            });

            session()->flash('success', 'Pelanggan ' . $userName . ' berhasil dihapus.');
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        } catch (QueryException $e) {
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
                    'operation' => 'Deleting user data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus pelanggan ' . $userName . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Deleting user data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        }
    }
}; ?>

<div>
    <x-datatable.table searchable="pelanggan">
        <x-slot name="head">
            <x-datatable.row>
                <x-datatable.heading align="center">No.</x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-40"
                    :direction="$sortField === 'name' ? $sortDirection : null "
                    wire:click="sortBy('name')"
                    align="left"
                >
                    Nama
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-40"
                    :direction="$sortField === 'email' ? $sortDirection : null "
                    wire:click="sortBy('email')"
                    align="left"
                >
                    Email
                </x-datatable.heading>
                <x-datatable.heading class="min-w-44" align="left">Nomor Handphone</x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-36"
                    :direction="$sortField === 'total_orders' ? $sortDirection : null "
                    wire:click="sortBy('total_orders')"
                    align="center"
                >
                    Total Pesanan
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-48"
                    :direction="$sortField === 'created_at' ? $sortDirection : null "
                    wire:click="sortBy('created_at')"
                    align="left"
                >
                    Dibuat Pada
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-48"
                    :direction="$sortField === 'updated_at' ? $sortDirection : null "
                    wire:click="sortBy('updated_at')"
                    align="left"
                >
                    Terakhir Diubah Pada
                </x-datatable.heading>
                <x-datatable.heading class="px-4 py-2"></x-datatable.heading>
            </x-datatable.row>
        </x-slot>
        <x-slot name="body">
            @forelse ($this->users as $user)
                <x-datatable.row
                    wire:key="{{ $user->id }}"
                    wire:loading.class="opacity-50"
                    wire:target="search,sortBy,resetSearch,perPage"
                >
                    <x-datatable.cell
                        class="text-nowrap text-sm font-normal tracking-tight text-black/70"
                        align="center"
                    >
                        {{ $loop->iteration . '.' }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-nowrap text-sm font-medium tracking-tight text-black" align="left">
                        {{ ucwords($user->name) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-nowrap text-sm font-medium tracking-tight text-black" align="left">
                        {{ $user->email }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-nowrap text-sm font-medium tracking-tight text-black" align="left">
                        {{ $user->phone_number ? '+62-' . $user->phone_number : '-' }}
                    </x-datatable.cell>
                    <x-datatable.cell
                        class="text-nowrap text-sm font-normal tracking-tight text-black/70"
                        align="center"
                    >
                        {{ $user->total_orders }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-nowrap text-sm font-normal tracking-tight text-black/70" align="left">
                        {{ formatTimestamp($user->created_at) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-nowrap text-sm font-normal tracking-tight text-black/70" align="left">
                        {{ formatTimestamp($user->updated_at) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="relative" align="right">
                        <x-common.dropdown width="48">
                            <x-slot name="trigger">
                                <button type="button" class="rounded-full p-2 text-black hover:bg-neutral-100">
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
                                @can('view account details')
                                    <x-common.dropdown-link
                                        :href="route('admin.users.show', ['id' => $user->id])"
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

                                @can('delete all accounts')
                                    <x-common.dropdown-link
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion-{{ $user->id }}')"
                                        class="text-red-500 hover:bg-red-50"
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
                                            <path d="M3 6h18" />
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                            <line x1="10" x2="10" y1="11" y2="17" />
                                            <line x1="14" x2="14" y1="11" y2="17" />
                                        </svg>
                                        Hapus
                                    </x-common.dropdown-link>
                                    <template x-teleport="body">
                                        <x-common.modal
                                            name="confirm-user-deletion-{{ $user->id }}"
                                            :show="$errors->isNotEmpty()"
                                            focusable
                                        >
                                            <div class="flex flex-col items-center p-6">
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
                                                <h2 class="mb-2 text-center text-black">
                                                    Hapus Pelanggan {{ ucwords($user->name) }}
                                                </h2>
                                                <p
                                                    class="mb-8 text-center text-base font-normal tracking-tight text-black/70"
                                                >
                                                    Apakah anda yakin ingin menghapus pelanggan
                                                    <strong>"{{ strtolower($user->name) }}"</strong>
                                                    ini ? Proses ini tidak dapat dibatalkan, seluruh data yang terkait
                                                    dengan pelanggan ini akan dihapus dari sistem.
                                                </p>
                                                <div
                                                    class="flex w-full flex-col items-center justify-end gap-4 md:flex-row"
                                                >
                                                    <x-common.button
                                                        variant="secondary"
                                                        class="w-full md:w-fit"
                                                        x-on:click="$dispatch('close')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="delete('{{ $user->id }}')"
                                                    >
                                                        Batal
                                                    </x-common.button>
                                                    <x-common.button
                                                        variant="danger"
                                                        class="w-full md:w-fit"
                                                        wire:click="delete('{{ $user->id }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="delete('{{ $user->id }}')"
                                                    >
                                                        <span
                                                            wire:loading.remove
                                                            wire:target="delete('{{ $user->id }}')"
                                                        >
                                                            Hapus Pelanggan
                                                        </span>
                                                        <span
                                                            wire:loading.flex
                                                            wire:target="delete('{{ $user->id }}')"
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
                            </x-slot>
                        </x-common.dropdown>
                    </x-datatable.cell>
                </x-datatable.row>
            @empty
                <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch,perPage">
                    <td class="p-4" colspan="8" align="center">
                        <figure class="my-4 flex h-full flex-col items-center justify-center">
                            <img
                                src="https://placehold.co/400"
                                class="mb-6 size-72 object-cover"
                                alt="Gambar ilustrasi pelanggan tidak ditemukan"
                            />
                            <figcaption class="flex flex-col items-center">
                                <h2 class="mb-3 text-center !text-2xl text-black">Pelanggan Tidak Ditemukan</h2>
                                <p class="text-center text-base font-normal tracking-tight text-black/70">
                                    @if ($search)
                                        Data pelanggan dengan nama
                                        <strong>"{{ $search }}"</strong>
                                        tidak ditemukan, silakan coba untuk mengubah kata kunci pencarian Anda.
                                    @else
                                            Seluruh pelanggan Anda akan ditampilkan di halaman ini.
                                    @endif
                                </p>
                            </figcaption>
                        </figure>
                    </td>
                </tr>
            @endforelse
        </x-slot>
        <x-slot name="loader">
            <div
                class="absolute left-1/2 top-[50%-1rem] h-full -translate-x-1/2 -translate-y-1/2"
                wire:loading
                wire:target="search,sortBy,resetSearch,perPage"
            >
                <div
                    class="inline-block size-10 animate-spin rounded-full border-4 border-current border-t-transparent text-primary"
                    role="status"
                    aria-label="loading"
                >
                    <span class="sr-only">Sedang diproses...</span>
                </div>
            </div>
        </x-slot>
        <x-slot name="pagination">
            {{ $this->users->links('components.common.pagination') }}
        </x-slot>
    </x-datatable.table>
</div>
