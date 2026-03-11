<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarrierAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('carrier_assignments')->insert([
            [
                'assignment_number' => 'CA-202403-00001',
                'job_order_id' => 1,
                'carrier_id' => 1,
                'vehicle_id' => 1,
                'assigned_by' => 1,
                'status' => 'confirmed',
                'quoted_price' => 1500000,
                'agreed_price' => 1400000,
                'currency' => 'IDR',
                'payment_term' => 'net30',
                'pickup_scheduled_at' => now()->addDay(),
                'delivery_scheduled_at' => now()->addDays(2),
                'spk_number' => 'SPK-001',
                'spk_sent_at' => now(),
                'notes' => 'Handle with care',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'assignment_number' => 'CA-202403-00002',
                'job_order_id' => 2,
                'carrier_id' => 2,
                'vehicle_id' => 3,
                'assigned_by' => 1,
                'status' => 'sent',
                'quoted_price' => 500000,
                'agreed_price' => null,
                'currency' => 'IDR',
                'payment_term' => 'net14',
                'pickup_scheduled_at' => now()->addDay(),
                'delivery_scheduled_at' => now()->addDays(1),
                'spk_number' => 'SPK-002',
                'spk_sent_at' => now(),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}