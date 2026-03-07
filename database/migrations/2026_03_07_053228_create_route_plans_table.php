<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_plans', function (Blueprint $table) {
            $table->id();
            $table->string('route_number')->unique(); // RT-202403-00001
            $table->foreignId('job_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            // Origin
            $table->string('origin_address');
            $table->decimal('origin_lat', 10, 7);
            $table->decimal('origin_lng', 10, 7);

            // Destination
            $table->string('destination_address');
            $table->decimal('destination_lat', 10, 7);
            $table->decimal('destination_lng', 10, 7);

            // Route info
            $table->decimal('total_distance_km', 10, 3)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->decimal('estimated_fuel_liters', 8, 2)->nullable();
            $table->decimal('estimated_toll_cost', 15, 2)->nullable();
            $table->decimal('estimated_fuel_cost', 15, 2)->nullable();
            $table->decimal('total_estimated_cost', 15, 2)->nullable();

            $table->enum('status', [
                'draft',
                'approved',
                'active',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->enum('route_type', ['fastest', 'shortest', 'cheapest'])->default('fastest');
            $table->json('waypoints')->nullable();       // titik-titik pemberhentian
            $table->json('polyline_points')->nullable(); // koordinat rute lengkap
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Waypoint/pemberhentian dalam rute
        Schema::create('route_waypoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_plan_id')->constrained()->onDelete('cascade');
            $table->integer('sequence');           // urutan pemberhentian
            $table->string('label')->nullable();   // misal: "Gudang A", "Pool"
            $table->string('address');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->enum('type', ['pickup', 'delivery', 'rest', 'fuel', 'toll', 'other'])
                  ->default('other');
            $table->integer('estimated_stop_minutes')->default(0);
            $table->dateTime('estimated_arrival_at')->nullable();
            $table->dateTime('actual_arrival_at')->nullable();
            $table->enum('status', ['pending', 'arrived', 'departed', 'skipped'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_waypoints');
        Schema::dropIfExists('route_plans');
    }
};