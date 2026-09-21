<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('work_reports', 'entry_time')) {
                $table->time('entry_time')->nullable()->after('report_date');
            }

            if (!Schema::hasColumn('work_reports', 'out_time')) {
                $table->time('out_time')->nullable()->after('entry_time');
            }

            if (!Schema::hasColumn('work_reports', 'activity_performed')) {
                $table->text('activity_performed')->nullable()->after('work_completed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            if (Schema::hasColumn('work_reports', 'activity_performed')) {
                $table->dropColumn('activity_performed');
            }

            if (Schema::hasColumn('work_reports', 'out_time')) {
                $table->dropColumn('out_time');
            }

            if (Schema::hasColumn('work_reports', 'entry_time')) {
                $table->dropColumn('entry_time');
            }
        });
    }
};
