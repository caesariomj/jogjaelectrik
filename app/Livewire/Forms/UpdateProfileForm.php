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
    public string $role;

    #[Validate]
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $province = '';

    public ?string $city = '';

    public string $address = '';

    public string $postalCode = '';

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
        $this->phone = $user->phone_number ? $this->safeDecrypt($user->phone_number) : '';

        if (! in_array($this->role, ['admin', 'super_admin'])) {
            $this->province = $user->city->province_id;
            $this->city = $user->city_id;
            $this->address = $this->safeDecrypt($user->address);
            $this->postalCode = $this->safeDecrypt($user->postal_code);
        }
    }

    private function safeDecrypt(string $encryptedValue)
    {
        try {
            return $encryptedValue ? Crypt::decryptString($encryptedValue) : '';
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return '';
        }
    }
}
