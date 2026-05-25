<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a strong master admin user for production
        User::firstOrCreate(
            ['email' => 'admin@timeflow.app'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('TimeFlowAdmin#2026_Secure!'), // Strong password
                'is_admin' => true,
                'timezone' => 'UTC',
            ]
        );
    }
}
