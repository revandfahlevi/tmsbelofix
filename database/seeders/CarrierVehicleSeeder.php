<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarrierVehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            [
                'carrier_id' => 1,
                'plate_number' => 'B 9123 TRK',
                'vehicle_type' => 'Truk',
                'brand' => 'Hino',
                'max_weight_kg' => 8000,
                'max_volume_m3' => 30,
            ],
            [
                'carrier_id' => 1,
                'plate_number' => 'B 8123 TRK',
                'vehicle_type' => 'Pickup',
                'brand' => 'L300',
                'max_weight_kg' => 2000,
                'max_volume_m3' => 10,
            ],
            [
                'carrier_id' => 2,
                'plate_number' => 'D 1234 EXP',
                'vehicle_type' => 'Van',
                'brand' => 'Granmax',
                'max_weight_kg' => 1000,
                'max_volume_m3' => 5,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            DB::table('carrier_vehicles')->insert([
                'carrier_id' => $vehicle['carrier_id'],
                'plate_number' => $vehicle['plate_number'],
                'vehicle_type' => $vehicle['vehicle_type'],
                'brand' => $vehicle['brand'],
                'max_weight_kg' => $vehicle['max_weight_kg'],
                'max_volume_m3' => $vehicle['max_volume_m3'],
                'status' => 'available',
                'stnk_expired_at' => now()->addYear(),
                'kir_expired_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}