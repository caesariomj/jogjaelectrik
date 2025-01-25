<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ReportPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view reports',
            'download reports',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        $superAdminRole = Role::findByName('super_admin');

        $superAdminRole->givePermissionTo('view reports');
        $superAdminRole->givePermissionTo('download reports');

        $this->command->info('Report permissions successfully seeded.');
    }
}
