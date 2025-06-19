<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UpdatePasswordForm extends Form
{
    public User $user;

    #[Validate]
    public string $current_password = '';

    #[Validate]
    public string $password = '';

    #[Validate]
    public string $password_confirmation = '';

    public function rules()
    {
        return [
            'current_password' => [
                'required',
                'string',
                'current_password',
            ],
            'password' => [
                'required',
                'string',
                Password::defaults(),
                'confirmed',
            ],
        ];
    }

    public function validationAttributes()
    {
        return [
            'current_password' => 'Password saat ini',
            'password' => 'Password baru',
            'password_confirmation' => 'Konfirmasi password baru',
        ];
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
