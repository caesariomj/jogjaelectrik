<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CheckoutPageAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::updateOrCreate(['name' => 'access admin page'], ['name' => 'access admin page']);

        $userRole = Role::findByName('user');

        $userRole->givePermissionTo('access admin page');
    }
}
