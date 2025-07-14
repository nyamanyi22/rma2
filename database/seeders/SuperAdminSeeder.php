<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@example.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'ChangeThis123!');

        if (!Admin::where('email', $email)->exists()) {
            Admin::create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'super_admin',
            ]);

            $this->command->info('✅ Super Admin created successfully.');
        } else {
            $this->command->info('ℹ️ Super Admin already exists.');
        }
    }
}
