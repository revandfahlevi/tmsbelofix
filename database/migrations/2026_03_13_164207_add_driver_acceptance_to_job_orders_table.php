<?php
// database/migrations/2026_03_13_000001_add_driver_acceptance_to_job_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->enum('driver_acceptance', ['none', 'waiting', 'accepted', 'rejected'])
                  ->default('none')
                  ->after('assigned_driver_id');
            $table->text('driver_rejection_reason')->nullable()->after('driver_acceptance');
            $table->timestamp('driver_responded_at')->nullable()->after('driver_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn(['driver_acceptance', 'driver_rejection_reason', 'driver_responded_at']);
        });
    }
};