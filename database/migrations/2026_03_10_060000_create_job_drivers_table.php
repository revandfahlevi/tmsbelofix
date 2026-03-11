<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend job_orders table jika belum ada kolom ini
        Schema::table('job_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('job_orders', 'assigned_driver_id')) {
                $table->foreignId('assigned_driver_id')->nullable()
                    ->constrained('users')->nullOnDelete()->after('created_by');
            }
            if (!Schema::hasColumn('job_orders', 'assigned_vehicle_id')) {
                $table->unsignedBigInteger('assigned_vehicle_id')->nullable()->after('assigned_driver_id');
            }
        });

        Schema::create('driver_job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['applied', 'accepted', 'rejected'])->default('applied');
            $table->text('driver_notes')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['job_order_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_job_applications');
    }
};