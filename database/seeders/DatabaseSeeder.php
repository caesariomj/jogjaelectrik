<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // ProvincesAndCitiesSeeder::class,
            RolesSeeder::class,
            AdminPageAccessSeeder::class,
            CategoryPermissionsSeeder::class,
            SubcategoryPermissionsSeeder::class,
            ProductPermissionsSeeder::class,
            DiscountPermissionsSeeder::class,
            CartPermissionsSeeder::class,
            CheckoutPageAccessSeeder::class,
            OrderPermissionsSeeder::class,
            PaymentPermissionsSeeder::class,
            RefundPermissionsSeeder::class,
            ProductReviewPermissionsSeeder::class,
            UserPermissionsSeeder::class,
            ReportPermissionsSeeder::class,
        ]);
    }
}
