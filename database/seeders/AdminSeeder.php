<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@tms.local'], [
            'name'              => 'Administrator TMS',
            'password'          => Hash::make('Admin@TMS2024!'),
            'role'              => 'admin',
            'status'            => 'active',
            'employee_id'       => 'ADM-0001-2024',
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Admin: admin@tms.local / Admin@TMS2024!');
    }
}