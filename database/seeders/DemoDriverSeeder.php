<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = [
            [
                'user' => [
                    'name' => 'Budi Santoso', 'email' => 'driver1@tms.local',
                    'password' => Hash::make('Driver@123!'), 'phone' => '081234567890',
                    'role' => 'driver', 'status' => 'active',
                    'employee_id' => 'DRV-0001-2024', 'email_verified_at' => now(),
                ],
                'profile' => [
                    'license_number' => 'SIM-B1-JKT-001234', 'license_type' => 'SIM_B1',
                    'license_expiry' => '2027-06-15', 'vehicle_type' => 'Truk Box 8 Ton',
                    'availability_status' => 'available',
                ],
            ],
            [
                'user' => [
                    'name' => 'Joko Widiarso', 'email' => 'driver2@tms.local',
                    'password' => Hash::make('Driver@123!'), 'phone' => '082345678901',
                    'role' => 'driver', 'status' => 'active',
                    'employee_id' => 'DRV-0002-2024', 'email_verified_at' => now(),
                ],
                'profile' => [
                    'license_number' => 'SIM-B2-JKT-005678', 'license_type' => 'SIM_B2',
                    'license_expiry' => '2026-12-01', 'vehicle_type' => 'Truk Trailer 20 Ton',
                    'availability_status' => 'off_duty',
                ],
            ],
        ];

        foreach ($drivers as $data) {
            $user = User::updateOrCreate(['email' => $data['user']['email']], $data['user']);
            $user->driverProfile()->updateOrCreate(['user_id' => $user->id], $data['profile']);
        }

        $this->command->info('✅ Demo drivers created: driver1@tms.local & driver2@tms.local / Driver@123!');
    }
}