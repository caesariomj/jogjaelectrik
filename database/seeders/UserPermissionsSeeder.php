<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UserPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view all accounts',
            'view own account',
            'view account details',
            'create accounts',
            'edit all accounts',
            'edit own account',
            'delete all accounts',
            'delete own account',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $userRole = Role::findByName('user');
        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $userRole->givePermissionTo('view own account');
        $userRole->givePermissionTo('edit own account');
        $userRole->givePermissionTo('delete own account');

        $adminRole->givePermissionTo('view own account');
        $adminRole->givePermissionTo('view all accounts');
        $adminRole->givePermissionTo('view account details');
        $adminRole->givePermissionTo('edit own account');

        $superAdminRole->givePermissionTo('view all accounts');
        $superAdminRole->givePermissionTo('view own account');
        $superAdminRole->givePermissionTo('view account details');
        $superAdminRole->givePermissionTo('create accounts');
        $superAdminRole->givePermissionTo('edit all accounts');
        $superAdminRole->givePermissionTo('edit own account');
        $superAdminRole->givePermissionTo('delete all accounts');

        $this->command->info('User permissions successfully seeded.');
    }
}
