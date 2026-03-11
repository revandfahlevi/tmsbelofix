<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DemoDriverSeeder::class,
            DemoUserSeeder::class,
            JobOrderSeeder::class, //wajib sblm carrier
            CarrierSeeder::class,
        CarrierVehicleSeeder::class,
        CarrierAssignmentSeeder::class,
        ]);
    }
}