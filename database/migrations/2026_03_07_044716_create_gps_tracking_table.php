<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Posisi GPS real-time driver
        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable(); // meter
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();  // 0-360 derajat
            $table->integer('battery_level')->nullable();  // 0-100%
            $table->boolean('is_online')->default(true);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['driver_id', 'recorded_at']);
            $table->index(['job_order_id', 'recorded_at']);
        });

        // History perjalanan (rute aktual)
        Schema::create('trip_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            $table->decimal('distance_from_prev_km', 8, 3)->nullable();
            $table->timestamp('recorded_at');

            $table->index(['job_order_id', 'recorded_at']);
        });

        // Geofence area (area yang dipantau)
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['circle', 'polygon'])->default('circle');
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lng', 10, 7)->nullable();
            $table->integer('radius_meters')->nullable(); // untuk circle
            $table->json('polygon_points')->nullable();   // untuk polygon
            $table->enum('trigger', ['enter', 'exit', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Log event geofence
        Schema::create('geofence_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geofence_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('event_type', ['enter', 'exit']);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofence_events');
        Schema::dropIfExists('geofences');
        Schema::dropIfExists('trip_tracks');
        Schema::dropIfExists('driver_locations');
    }
};