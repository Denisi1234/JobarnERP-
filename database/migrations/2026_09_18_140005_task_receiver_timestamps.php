<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks', 'accepted_at')) {
                    $table->timestamp('accepted_at')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('tasks', 'started_at')) {
                    $table->timestamp('started_at')->nullable()->after('accepted_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                foreach (['accepted_at', 'started_at'] as $col) {
                    if (Schema::hasColumn('tasks', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
