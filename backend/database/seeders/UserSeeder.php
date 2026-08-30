<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@example.com',
                'type' => UserType::Admin,
                'role' => 'Product Manager',
            ],
            [
                'name' => 'Product Manager',
                'email' => 'manager@example.com',
                'type' => UserType::User,
                'role' => 'Product Manager',
            ],
            [
                'name' => 'Product Editor',
                'email' => 'editor@example.com',
                'type' => UserType::User,
                'role' => 'Product Editor',
            ],
            [
                'name' => 'Product Viewer',
                'email' => 'viewer@example.com',
                'type' => UserType::User,
                'role' => 'Viewer',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password',
                    'type' => $userData['type'],
                    'status' => Status::Active,
                ],
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
