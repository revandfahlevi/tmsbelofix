<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->date('license_expiry');
            $table->string('license_number')->unique();
            $table->string('license_type')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->enum('availability_status', ['available', 'on_duty', 'off_duty', 'break'])->default('off_duty');
            $table->decimal('current_lat', 10, 8)->nullable();
            $table->decimal('current_lng', 11, 8)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->integer('total_deliveries')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};