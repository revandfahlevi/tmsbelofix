<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master data carrier/vendor
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // CAR-001
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('pic_name')->nullable();   // Person in charge
            $table->string('pic_phone')->nullable();
            $table->enum('type', ['trucking', 'shipping', 'airline', 'courier', 'own_fleet'])
                  ->default('trucking');
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->decimal('rating', 3, 2)->default(0.00); // 0.00 - 5.00
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Armada/kendaraan milik carrier
        Schema::create('carrier_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->onDelete('cascade');
            $table->string('plate_number')->unique();
            $table->string('vehicle_type');   // Truk, pickup, container
            $table->string('brand')->nullable();
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->decimal('max_volume_m3', 10, 2)->nullable();
            $table->enum('status', ['available', 'on_trip', 'maintenance', 'inactive'])
                  ->default('available');
            $table->date('stnk_expired_at')->nullable();
            $table->date('kir_expired_at')->nullable();
            $table->timestamps();
        });

        // Assignment: job order → carrier
        Schema::create('carrier_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_number')->unique(); // CA-202403-00001
            $table->foreignId('job_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('carrier_id')->constrained()->onDelete('restrict');
            $table->foreignId('vehicle_id')->nullable()
                  ->constrained('carrier_vehicles')->nullOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('restrict');

            $table->enum('status', [
                'draft',
                'sent',        // SPK sudah dikirim ke carrier
                'confirmed',   // Carrier konfirmasi
                'rejected',    // Carrier tolak
                'in_progress',
                'completed',
                'cancelled',
            ])->default('draft');

            // Harga
            $table->decimal('quoted_price', 15, 2)->nullable();   // Penawaran carrier
            $table->decimal('agreed_price', 15, 2)->nullable();   // Harga deal
            $table->string('currency', 3)->default('IDR');
            $table->enum('payment_term', ['cash', 'net7', 'net14', 'net30'])->default('net30');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // Jadwal
            $table->dateTime('pickup_scheduled_at')->nullable();
            $table->dateTime('delivery_scheduled_at')->nullable();
            $table->dateTime('actual_pickup_at')->nullable();
            $table->dateTime('actual_delivery_at')->nullable();

            // Dokumen
            $table->string('spk_number')->nullable();    // Surat Perintah Kerja
            $table->dateTime('spk_sent_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_assignments');
        Schema::dropIfExists('carrier_vehicles');
        Schema::dropIfExists('carriers');
    }
};