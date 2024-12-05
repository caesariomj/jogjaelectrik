<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class OrderPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view all orders',
            'view own orders',
            'create orders',
            'cancel all orders',
            'cancel own orders',
            'update orders',
            'delete orders',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $userRole = Role::findByName('user');
        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $userRole->givePermissionTo('view own orders');
        $userRole->givePermissionTo('create orders');
        $userRole->givePermissionTo('cancel own orders');

        $adminRole->givePermissionTo('view all orders');
        $adminRole->givePermissionTo('cancel all orders');
        $adminRole->givePermissionTo('update orders');
        $adminRole->givePermissionTo('delete orders');

        $superAdminRole->givePermissionTo('view all orders');
        $superAdminRole->givePermissionTo('cancel all orders');
        $superAdminRole->givePermissionTo('update orders');
        $superAdminRole->givePermissionTo('delete orders');

        $this->command->info('Order permissions successfully seeded.');
    }
}
