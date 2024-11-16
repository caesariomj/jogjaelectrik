<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProductPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'create products',
            'edit products',
            'archive products',
            'restore products',
            'force delete products',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $adminRole->givePermissionTo('create products');
        $adminRole->givePermissionTo('edit products');
        $adminRole->givePermissionTo('archive products');
        $adminRole->givePermissionTo('restore products');
        $superAdminRole->givePermissionTo('create products');
        $superAdminRole->givePermissionTo('edit products');
        $superAdminRole->givePermissionTo('archive products');
        $superAdminRole->givePermissionTo('restore products');
        $superAdminRole->givePermissionTo('force delete products');
    }
}
