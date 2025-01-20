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
    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function rules()
    {
        return [
            'currentPassword' => [
                'required',
                'string',
                'current_password',
            ],
            'password' => [
                'required',
                'string',
                Password::defaults(),
                'confirmed:passwordConfirmation',
            ],
            'passwordConfirmation' => [
                'required',
                'string',
            ],
        ];
    }

    public function validationAttributes()
    {
        return [
            'currentPassword' => 'Password saat ini',
            'password' => 'Password baru',
            'passwordConfirmation' => 'Konfirmasi password baru',
        ];
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
