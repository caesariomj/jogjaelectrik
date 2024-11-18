<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DiscountPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view discount details',
            'create discounts',
            'edit discounts',
            'delete discounts',
            'manage discount usage',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $adminRole->givePermissionTo('view discount details');
        $adminRole->givePermissionTo('create discounts');
        $adminRole->givePermissionTo('edit discounts');
        $adminRole->givePermissionTo('delete discounts');
        $superAdminRole->givePermissionTo('view discount details');
        $superAdminRole->givePermissionTo('create discounts');
        $superAdminRole->givePermissionTo('edit discounts');
        $superAdminRole->givePermissionTo('delete discounts');
        $superAdminRole->givePermissionTo('manage discount usage');
    }
}
