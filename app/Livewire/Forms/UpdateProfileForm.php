<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UpdateProfileForm extends Form
{
    public User $user;

    #[Validate]
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $province = '';

    public ?string $city = '';

    public string $address = '';

    public string $postalCode = '';

    public function rules()
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
                'required',
                'phone:ID',
            ],
            'province' => [
                'required',
                'numeric',
                'exists:provinces,id',
            ],
            'city' => [
                'required',
                'numeric',
                'exists:cities,id',
            ],
            'address' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'postalCode' => [
                'required',
                'numeric',
                'digits:5',
            ],
        ];
    }

    public function validationAttributes()
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
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $this->safeDecrypt($user->phone_number);
        $this->province = $user->city->province_id;
        $this->city = $user->city_id;
        $this->address = $this->safeDecrypt($user->address);
        $this->postalCode = $this->safeDecrypt($user->postal_code);
    }

    private function safeDecrypt($encryptedValue)
    {
        try {
            return $encryptedValue ? Crypt::decryptString($encryptedValue) : '';
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return '';
        }
    }
}
