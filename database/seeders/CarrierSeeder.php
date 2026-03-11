<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CarrierSeeder extends Seeder
{
    public function run(): void
    {
        $carriers = [
            [
                'code' => 'CAR-001',
                'name' => 'PT Nusantara Logistics',
                'phone' => '081234567890',
                'email' => 'info@nusantaralogistics.com',
                'city' => 'Jakarta',
                'type' => 'trucking',
                'status' => 'active',
                'rating' => 4.5
            ],
            [
                'code' => 'CAR-002',
                'name' => 'PT Cepat Express',
                'phone' => '081298765432',
                'email' => 'cs@cepatexpress.co.id',
                'city' => 'Bandung',
                'type' => 'courier',
                'status' => 'active',
                'rating' => 4.2
            ],
            [
                'code' => 'CAR-003',
                'name' => 'PT Samudra Shipping',
                'phone' => '081377788899',
                'email' => 'support@samudra.co.id',
                'city' => 'Surabaya',
                'type' => 'shipping',
                'status' => 'active',
                'rating' => 4.7
            ],
        ];

        foreach ($carriers as $carrier) {
            DB::table('carriers')->insert([
                'code' => $carrier['code'],
                'name' => $carrier['name'],
                'phone' => $carrier['phone'],
                'email' => $carrier['email'],
                'city' => $carrier['city'],
                'pic_name' => 'Budi Santoso',
                'pic_phone' => '08123456789',
                'type' => $carrier['type'],
                'status' => $carrier['status'],
                'rating' => $carrier['rating'],
                'notes' => 'Seeder data',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}