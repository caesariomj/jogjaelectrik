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
            'create accounts',
            'update all accounts',
            'update own account',
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
        $userRole->givePermissionTo('update own account');
        $userRole->givePermissionTo('delete own account');

        $adminRole->givePermissionTo('view own account');
        $adminRole->givePermissionTo('view all accounts');
        $adminRole->givePermissionTo('update own account');

        $superAdminRole->givePermissionTo('view all accounts');
        $superAdminRole->givePermissionTo('view own account');
        $superAdminRole->givePermissionTo('create accounts');
        $superAdminRole->givePermissionTo('update all accounts');
        $superAdminRole->givePermissionTo('update own account');
        $superAdminRole->givePermissionTo('delete all accounts');

        $this->command->info('User permissions successfully seeded.');
    }
}
