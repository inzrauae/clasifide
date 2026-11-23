<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            PackageSeeder::class,
            AdminSeeder::class,
        ]);

        // Demo content only outside production (or when asked for explicitly)
        if (! app()->isProduction() || env('SEED_DEMO', false)) {
            $this->call(DemoSeeder::class);
        }
    }
}
