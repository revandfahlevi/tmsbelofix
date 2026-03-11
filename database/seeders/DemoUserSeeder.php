<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'user@tms.local'],
            [
                'name'              => 'Customer User',
                'email'             => 'user@tms.local',
                'password'          => Hash::make('User@TMS2024!'),
                'role'              => 'user',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'user2@tms.local'],
            [
                'name'              => 'Customer Dua',
                'email'             => 'user2@tms.local',
                'password'          => Hash::make('User@TMS2024!'),
                'role'              => 'user',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Demo users seeded:');
        $this->command->info('  User1: user@tms.local  / User@TMS2024!');
        $this->command->info('  User2: user2@tms.local / User@TMS2024!');
    }
}