<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_number')->unique();
            $table->foreignId('job_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('driver_id')->constrained('users')->onDelete('restrict');
            $table->unsignedBigInteger('vehicle_id')->nullable(); // dari carrier vehicles
            $table->foreignId('dispatched_by')->constrained('users')->onDelete('restrict');
            $table->enum('status', [
                'draft', 'confirmed', 'departed', 'arrived',
                'loading', 'in_transit', 'delivered', 'cancelled'
            ])->default('draft');
            $table->datetime('scheduled_departure')->nullable();
            $table->datetime('actual_departure')->nullable();
            $table->datetime('scheduled_arrival')->nullable();
            $table->datetime('actual_arrival')->nullable();
            $table->text('dispatch_notes')->nullable();
            $table->json('checklist')->nullable(); // pre-departure checklist
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dispatch_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained()->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('restrict');
            $table->string('from_status');
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_status_logs');
        Schema::dropIfExists('dispatches');
    }
};