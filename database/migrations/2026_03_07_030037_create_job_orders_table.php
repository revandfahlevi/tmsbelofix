<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique(); // JO-2024-00001
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();

            // Customer
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();

            // Shipment
            $table->string('origin_address');
            $table->string('origin_city');
            $table->decimal('origin_lat', 10, 7)->nullable();
            $table->decimal('origin_lng', 10, 7)->nullable();

            $table->string('destination_address');
            $table->string('destination_city');
            $table->decimal('destination_lat', 10, 7)->nullable();
            $table->decimal('destination_lng', 10, 7)->nullable();

            // Cargo
            $table->string('cargo_type');
            $table->decimal('cargo_weight_kg', 10, 2)->nullable();
            $table->decimal('cargo_volume_m3', 10, 2)->nullable();
            $table->text('cargo_description')->nullable();
            $table->boolean('is_hazardous')->default(false);

            // Schedule
            $table->dateTime('pickup_scheduled_at')->nullable();
            $table->dateTime('delivery_scheduled_at')->nullable();
            $table->dateTime('pickup_actual_at')->nullable();
            $table->dateTime('delivery_actual_at')->nullable();

            // Status
            $table->enum('status', [
                'draft',
                'pending',
                'assigned',
                'in_progress',
                'picked_up',
                'in_transit',
                'delivered',
                'completed',
                'cancelled',
                'failed',
            ])->default('draft');

            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            // Financial
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // Notes
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('changed_by')->constrained('users')->onDelete('restrict');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_status_logs');
        Schema::dropIfExists('job_orders');
    }
};