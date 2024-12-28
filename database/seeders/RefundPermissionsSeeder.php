<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RefundPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view refunds',
            'view refund details',
            'create refunds',
            'process refunds',
            'reject refunds',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $userRole = Role::findByName('user');
        $adminRole = Role::findByName('admin');
        $superAdminRole = Role::findByName('super_admin');

        $userRole->givePermissionTo('create refunds');
        $userRole->givePermissionTo('view refund details');

        $adminRole->givePermissionTo('view refunds');
        $adminRole->givePermissionTo('view refund details');

        $superAdminRole->givePermissionTo('view refunds');
        $superAdminRole->givePermissionTo('view refund details');
        $superAdminRole->givePermissionTo('process refunds');
        $superAdminRole->givePermissionTo('reject refunds');

        $this->command->info('Refund permissions successfully seeded.');
    }
}
