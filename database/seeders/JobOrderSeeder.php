<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
 use Illuminate\Support\Facades\DB;

class JobOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
  

public function run(): void
{
    DB::table('job_orders')->insert([
        [
            'id' => 1,
            'job_number' => 'JOB-001',
            'created_by' => 1,

            'customer_name' => 'PT ABC',
            'customer_phone' => '081234567890',
            'customer_email' => 'logistics@abc.com',

            'origin_address' => 'Jl. Sudirman No.1',
            'origin_city' => 'Jakarta',

            'destination_address' => 'Jl. Asia Afrika No.10',
            'destination_city' => 'Bandung',

            'cargo_type' => 'General Cargo',
            'cargo_weight_kg' => 1000,
            'cargo_volume_m3' => 5,

            'pickup_scheduled_at' => now()->addDay(),
            'delivery_scheduled_at' => now()->addDays(2),

            'status' => 'pending',
            'priority' => 'normal',
            'payment_status' => 'unpaid',

            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'id' => 2,
            'job_number' => 'JOB-002',
            'created_by' => 1,

            'customer_name' => 'PT XYZ',
            'customer_phone' => '081298765432',
            'customer_email' => 'shipping@xyz.com',

            'origin_address' => 'Jl. Dago No.20',
            'origin_city' => 'Bandung',

            'destination_address' => 'Jl. Tunjungan No.5',
            'destination_city' => 'Surabaya',

            'cargo_type' => 'Electronics',
            'cargo_weight_kg' => 500,
            'cargo_volume_m3' => 2,

            'pickup_scheduled_at' => now()->addDay(),
            'delivery_scheduled_at' => now()->addDays(3),

            'status' => 'pending',
            'priority' => 'normal',
            'payment_status' => 'unpaid',

            'created_at' => now(),
            'updated_at' => now()
        ]
    ]);
}
}
