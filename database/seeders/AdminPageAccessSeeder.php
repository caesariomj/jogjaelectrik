<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminPageAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::updateOrCreate(['name' => 'access admin page'], ['name' => 'access admin page']);

        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $adminRole->givePermissionTo('access admin page');
        $superAdminRole->givePermissionTo('access admin page');
    }
}
