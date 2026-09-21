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
            if (!Schema::hasColumn('it_tickets', 'technician_notes')) {
                $table->text('technician_notes')->nullable()->after('handover_note');
            }
            if (!Schema::hasColumn('it_tickets', 'diagnosis')) {
                $table->text('diagnosis')->nullable()->after('technician_notes');
            }
            if (!Schema::hasColumn('it_tickets', 'action_taken')) {
                $table->text('action_taken')->nullable()->after('diagnosis');
            }
            if (!Schema::hasColumn('it_tickets', 'resolution_summary')) {
                $table->text('resolution_summary')->nullable()->after('action_taken');
            }
            if (!Schema::hasColumn('it_tickets', 'work_completed')) {
                $table->text('work_completed')->nullable()->after('resolution_summary');
            }
            if (!Schema::hasColumn('it_tickets', 'customer_followup')) {
                $table->string('customer_followup')->nullable()->after('work_completed');
            }
            if (!Schema::hasColumn('it_tickets', 'attachments')) {
                $table->json('attachments')->nullable()->after('customer_followup');
            }
            if (!Schema::hasColumn('it_tickets', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('it_tickets', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('it_tickets', 'return_reason')) {
                $table->text('return_reason')->nullable()->after('started_at');
            }
        });

        if (Schema::hasTable('visit_timelines')) {
            Schema::table('visit_timelines', function (Blueprint $table) {
                if (!Schema::hasColumn('visit_timelines', 'it_ticket_id')) {
                    $table->foreignId('it_ticket_id')->nullable()->after('visit_id')->constrained('it_tickets')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('visit_timelines')) {
            Schema::table('visit_timelines', function (Blueprint $table) {
                if (Schema::hasColumn('visit_timelines', 'it_ticket_id')) {
                    $table->dropForeign(['it_ticket_id']);
                    $table->dropColumn('it_ticket_id');
                }
            });
        }

        Schema::table('it_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'technician_notes',
                'diagnosis',
                'action_taken',
                'resolution_summary',
                'work_completed',
                'customer_followup',
                'attachments',
                'accepted_at',
                'started_at',
                'return_reason',
            ]);
        });
    }
};
