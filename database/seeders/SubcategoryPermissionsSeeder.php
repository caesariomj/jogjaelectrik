<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SubcategoryPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view subcategory details',
            'create subcategories',
            'edit subcategories',
            'delete subcategories',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $roles = ['admin', 'super_admin'];

        foreach ($roles as $roleName) {
            $role = Role::findByName($roleName);
            $role->givePermissionTo($permissions);
        }
    }
}
