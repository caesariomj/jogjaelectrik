<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CategoryPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view category details',
            'create categories',
            'edit categories',
            'delete categories',
            'set primary categories',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $adminRole->givePermissionTo('create categories');
        $adminRole->givePermissionTo('edit categories');
        $adminRole->givePermissionTo('delete categories');
        $superAdminRole->givePermissionTo('create categories');
        $superAdminRole->givePermissionTo('edit categories');
        $superAdminRole->givePermissionTo('delete categories');
        $superAdminRole->givePermissionTo('set primary categories');
    }
}
