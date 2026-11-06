<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('The demo administrator can only be seeded in the local environment.');
        }

        $username = config('auth.demo_admin.username');
        $email = config('auth.demo_admin.email');
        $password = config('auth.demo_admin.password');

        if (! config('auth.demo_admin.enabled') || ! is_string($password) || $password === '') {
            throw new RuntimeException('Enable and configure the local demo administrator before seeding.');
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $username,
                'password' => Hash::make($password),
            ],
        );
    }
}
