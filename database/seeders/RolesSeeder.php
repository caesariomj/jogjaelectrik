<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Create a user roles for the application.
     */
    public function run(): void
    {
        $roles = [
            'user',
            'admin',
            'super_admin',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role], ['name' => $role]);
        }
    }
}
