<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UpdateProfileForm extends Form
{
    public User $user;

    #[Locked]
    public string $role = '';

    #[Validate]
    public string $name = '';

    #[Validate]
    public string $email = '';

    #[Validate]
    public ?string $phone = null;

    #[Validate]
    public ?int $province = null;

    #[Validate]
    public ?int $city = null;

    #[Validate]
    public ?string $address = null;

    #[Validate]
    public ?string $postalCode = null;

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email,'.$this->user->id,
            ],
            'phone' => [
                ! in_array($this->role, ['admin', 'super_admin']) ? 'required' : 'nullable',
                'phone:ID',
            ],
            'province' => [
                ! in_array($this->role, ['admin', 'super_admin']) ? 'required' : 'nullable',
                'numeric',
                'exists:provinces,id',
            ],
            'city' => [
                ! in_array($this->role, ['admin', 'super_admin']) ? 'required' : 'nullable',
                'numeric',
                'exists:cities,id',
            ],
            'address' => [
                ! in_array($this->role, ['admin', 'super_admin']) ? 'required' : 'nullable',
                'string',
                'min:10',
                'max:1000',
            ],
            'postalCode' => [
                ! in_array($this->role, ['admin', 'super_admin']) ? 'required' : 'nullable',
                'numeric',
                'digits:5',
            ],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'name' => 'Nama lengkap',
            'email' => 'Alamat email',
            'phone' => 'Nomor telefon',
            'province' => 'Provinsi',
            'city' => 'Kabupaten/Kota',
            'address' => 'Alamat lengkap',
            'postalCode' => 'Kode pos',
        ];
    }

    public function setUser(User $user)
    {
        $this->role = $user->roles()->first()->name;
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone_number ? Crypt::decryptString($user->phone_number) : null;

        if (! in_array($this->role, ['admin', 'super_admin'])) {
            $this->province = $user->city_id ? $user->city->province_id : null;
            $this->city = $user->city_id ? $user->city_id : null;
            $this->address = $user->address ? Crypt::decryptString($user->address) : null;
            $this->postalCode = $user->postal_code ? Crypt::decryptString($user->postal_code) : null;
        }
    }
}
