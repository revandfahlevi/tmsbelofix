<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proof_of_deliveries', function (Blueprint $table) {
            // Kolom baru yang belum ada
            $table->string('pod_number')->unique()->after('id')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->after('driver_id');
            $table->string('recipient_relationship')->nullable()->after('recipient_phone');
            $table->text('rejection_reason')->nullable()->after('dispute_reason');
            $table->timestamp('verified_at')->nullable()->after('confirmed_at');
            $table->json('photos')->nullable()->after('photo_paths');
            $table->text('notes')->nullable()->after('delivery_notes');

            // Rename kolom lama supaya kompatibel
            // delivered_lat → delivery_lat
            if (Schema::hasColumn('proof_of_deliveries', 'delivered_lat')) {
                $table->renameColumn('delivered_lat', 'delivery_lat');
            }
            if (Schema::hasColumn('proof_of_deliveries', 'delivered_lng')) {
                $table->renameColumn('delivered_lng', 'delivery_lng');
            }
            if (Schema::hasColumn('proof_of_deliveries', 'confirmed_by')) {
                $table->renameColumn('confirmed_by', 'verified_by_old');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proof_of_deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'pod_number', 'recipient_relationship',
                'rejection_reason', 'verified_at', 'photos', 'notes',
            ]);
            $table->dropForeign(['verified_by']);
            $table->dropColumn('verified_by');
        });
    }
};