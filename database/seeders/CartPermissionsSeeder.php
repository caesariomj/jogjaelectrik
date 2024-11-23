<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CartPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view own cart',
            'create cart',
            'edit cart',
            'delete cart',
            'add items',
            'update items',
            'remove items',
            'apply discounts',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $userRole = Role::findByName('user');

        $userRole->givePermissionTo($permissions);
    }
}
