<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visit_timelines')) {
            // Make visit_id nullable to allow IT tickets without a visit (e.g., direct IT creation)
            // Need to drop foreign key first, then alter column, then re-add
            try {
                Schema::table('visit_timelines', function (Blueprint $table) {
                    // Drop foreign key if exists
                    try {
                        $table->dropForeign(['visit_id']);
                    } catch (\Throwable $e) {
                        // ignore if not exists
                    }
                });
            } catch (\Throwable $e) {}

            // For pgsql, use raw SQL to make nullable
            try {
                \DB::statement('ALTER TABLE visit_timelines ALTER COLUMN visit_id DROP NOT NULL');
            } catch (\Throwable $e) {
                // Fallback for other DBs
                Schema::table('visit_timelines', function (Blueprint $table) {
                    $table->foreignId('visit_id')->nullable()->change();
                });
            }

            // Re-add foreign key with nullOnDelete
            Schema::table('visit_timelines', function (Blueprint $table) {
                $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
            });

            // Also make it_ticket_id nullable if not already (for visit-only timelines)
            try {
                Schema::table('visit_timelines', function (Blueprint $table) {
                    try { $table->dropForeign(['it_ticket_id']); } catch (\Throwable $e) {}
                });
                \DB::statement('ALTER TABLE visit_timelines ALTER COLUMN it_ticket_id DROP NOT NULL');
                Schema::table('visit_timelines', function (Blueprint $table) {
                    $table->foreign('it_ticket_id')->references('id')->on('it_tickets')->nullOnDelete();
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        // No rollback needed for nullable change
    }
};
