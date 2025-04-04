<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rules;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ResetPasswordForm extends Form
{
    #[Locked]
    public string $token = '';

    #[Validate]
    public string $email = '';

    #[Validate]
    public string $password = '';

    #[Validate]
    public string $password_confirmation = '';

    protected function rules()
    {
        return [
            'token' => [
                'required',
            ],
            'email' => [
                'required',
                'string',
                'email',
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ];
    }

    protected function validationAttributes()
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Konfirmasi password',
        ];
    }
}
