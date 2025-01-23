<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AdminForm extends Form
{
    public ?User $user = null;

    #[Validate]
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

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
                is_null($this->user) ? 'unique:users,email' : 'unique:users,email,'.$this->user->id,
            ],
            'password' => [
                is_null($this->user) ? 'required' : 'nullable',
                'string',
                Password::defaults(),
                'confirmed:passwordConfirmation',
            ],
            'passwordConfirmation' => [
                is_null($this->user) ? 'required' : 'nullable',
                'string',
            ],
        ];
    }

    public function validationAttributes()
    {
        return [
            'name' => 'Nama',
            'email' => 'Alamat email',
            'password' => 'Password admin',
            'passwordConfirmation' => 'Konfirmasi password admin',
        ];
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
    }
}
