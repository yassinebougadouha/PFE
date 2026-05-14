<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Define roles and their client types
        $roles = [
            'admin' => null,
            'super_admin' => null,
            'client' => 'client',
            'user' => 'user',
        ];

        // Create 5 users for each role
        foreach ($roles as $role => $clientType) {
            User::factory()->count(5)->create([
                'role' => $role,
                'client_type' => $clientType,
                'password' => Hash::make('password'),
            ]);
        }

        // Create a specific admin user for easy access
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'client_type' => null,
                'is_active' => true,
                'profile_completed' => true,
            ],
            
        );
        User::firstOrCreate(

            ['email' => 'aminbouhlel041@gmail.com'],
            [
                'name' => 'Super Admin User',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'client_type' => null,
                'is_active' => true,
                'profile_completed' => true,
            ],
            
        );
        User::firstOrCreate(

            ['email' => 'client@example.com'],
            [
                'name' => 'Client User',
                'password' => Hash::make('password'),
                'role' => 'client',
                'client_type' => 'client',
                'is_active' => true,
                'profile_completed' => true,
            ],
            
        );
    }
}