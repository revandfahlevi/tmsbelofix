<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
    {
        Schema::table('proof_of_deliveries', function (Blueprint $table) {

            if (!Schema::hasColumn('proof_of_deliveries', 'pod_number')) {
                $table->string('pod_number')->unique()->nullable()->after('id');
            }

            if (!Schema::hasColumn('proof_of_deliveries', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->after('driver_id');
            }

            if (!Schema::hasColumn('proof_of_deliveries', 'recipient_relationship')) {
                $table->string('recipient_relationship')->nullable()->after('recipient_phone');
            }

            if (!Schema::hasColumn('proof_of_deliveries', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('proof_of_deliveries', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('delivered_at');
            }

            if (!Schema::hasColumn('proof_of_deliveries', 'photos')) {
                $table->json('photos')->nullable()->after('signature_path');
            }

            if (!Schema::hasColumn('proof_of_deliveries', 'notes')) {
                $table->text('notes')->nullable();
            }

            // Rename kolom lama jika ada
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

            if (Schema::hasColumn('proof_of_deliveries', 'pod_number')) {
                $table->dropColumn('pod_number');
            }

            if (Schema::hasColumn('proof_of_deliveries', 'recipient_relationship')) {
                $table->dropColumn('recipient_relationship');
            }

            if (Schema::hasColumn('proof_of_deliveries', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }

            if (Schema::hasColumn('proof_of_deliveries', 'verified_at')) {
                $table->dropColumn('verified_at');
            }

            if (Schema::hasColumn('proof_of_deliveries', 'photos')) {
                $table->dropColumn('photos');
            }

            if (Schema::hasColumn('proof_of_deliveries', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('proof_of_deliveries', 'verified_by')) {
                $table->dropForeign(['verified_by']);
                $table->dropColumn('verified_by');
            }
        });
    }
    
};