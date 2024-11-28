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
        Permission::updateOrCreate(['name' => 'access checkout page'], ['name' => 'access checkout page']);

        $userRole = Role::findByName('user');

        $userRole->givePermissionTo('access checkout page');
    }
}
