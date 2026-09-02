<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('provinces')->exists()) {
            return;
        }

        $files = [
            'provinces.sql',
            'cities.sql',
            'districts.sql',
        ];

        foreach ($files as $file) {
            $sql = file_get_contents(
                database_path("data/{$file}")
            );

            DB::unprepared($sql);
        }
    }
}