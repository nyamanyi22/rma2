<?php

namespace Database\Seeders;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'superadmin@example.com';

        if (!Admin::where('email', $email)->exists()) {
            Admin::create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => $email,
                'password' => Hash::make('ChangeThis123!'), // Change this in production
                'role' => 'super_admin',
            ]);

            $this->command->info('✅ Super Admin created successfully.');
        } else {
            $this->command->info('ℹ️ Super Admin already exists.');
        }

    }}
