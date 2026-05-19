<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@testing.com', 'role' => 'admin'],
            ['name' => 'John Doe', 'email' => 'john@testing.com', 'role' => 'user'],
            ['name' => 'Jane Smith', 'email' => 'jane@testing.com', 'role' => 'user'],
            ['name' => 'Bob Wilson', 'email' => 'bob@testing.com', 'role' => 'user'],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole($userData['role']);
        }
    }
}
