<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PaymentPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'pay orders',
            'view all payments',
            'view own payments',
            'refund payments',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $userRole = Role::findByName('user');
        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $userRole->givePermissionTo('pay orders');
        $userRole->givePermissionTo('view own payments');

        $adminRole->givePermissionTo('view all payments');

        $superAdminRole->givePermissionTo('view all payments');
        $superAdminRole->givePermissionTo('refund payments');

        $this->command->info('Payment permissions successfully seeded.');
    }
}
