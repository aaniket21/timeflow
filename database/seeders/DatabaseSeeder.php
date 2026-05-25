<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always run the AdminSeeder to ensure an admin exists in production
        $this->call(AdminSeeder::class);

        // Only run the DemoSeeder in local/development environments
        if (!App::environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
