<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DeleteUserForm extends Form
{
    public User $user;

    #[Validate]
    public string $password = '';

    public function rules()
    {
        return [
            'password' => [
                'required',
                'string',
                'current_password',
            ],
        ];
    }

    public function validationAttributes()
    {
        return [
            'password' => 'Password',
        ];
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
