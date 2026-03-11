<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proof_of_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');

            $table->string('pod_number')->unique();
            $table->enum('status', ['pending', 'submitted', 'verified', 'rejected'])->default('pending');

            // Penerima
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_relationship')->nullable(); // diri sendiri, keluarga, sekuriti, dll

            // Waktu
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Bukti
            $table->json('photos')->nullable();         // array path foto
            $table->string('signature_path')->nullable(); // path tanda tangan
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Koordinat saat delivery
            $table->decimal('delivery_lat', 10, 7)->nullable();
            $table->decimal('delivery_lng', 10, 7)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proof_of_deliveries');
    }
};