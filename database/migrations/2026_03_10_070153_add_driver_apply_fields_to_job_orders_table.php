<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Cek dulu kalau belum ada
            if (!Schema::hasColumn('job_orders', 'applicant_driver_id')) {
                $table->foreignId('applicant_driver_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('assigned_driver_id');
            }
            if (!Schema::hasColumn('job_orders', 'driver_notes')) {
                $table->text('driver_notes')->nullable()->after('applicant_driver_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropForeign(['applicant_driver_id']);
            $table->dropColumn(['applicant_driver_id', 'driver_notes']);
        });
    }
};