<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('it_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('it_tickets', 'qa_checklist')) {
                $table->json('qa_checklist')->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('it_tickets', 'spare_parts')) {
                $table->json('spare_parts')->nullable()->after('qa_checklist');
            }
            if (!Schema::hasColumn('it_tickets', 'device_specs')) {
                $table->json('device_specs')->nullable()->after('spare_parts');
            }
            if (!Schema::hasColumn('it_tickets', 'handover_notes')) {
                $table->text('handover_notes')->nullable()->after('device_specs');
            }
            if (!Schema::hasColumn('it_tickets', 'reassigned_at')) {
                $table->timestamp('reassigned_at')->nullable()->after('handover_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('it_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('it_tickets', 'qa_checklist')) {
                $table->dropColumn('qa_checklist');
            }
            if (Schema::hasColumn('it_tickets', 'spare_parts')) {
                $table->dropColumn('spare_parts');
            }
            if (Schema::hasColumn('it_tickets', 'device_specs')) {
                $table->dropColumn('device_specs');
            }
            if (Schema::hasColumn('it_tickets', 'handover_notes')) {
                $table->dropColumn('handover_notes');
            }
            if (Schema::hasColumn('it_tickets', 'reassigned_at')) {
                $table->dropColumn('reassigned_at');
            }
        });
    }
};
