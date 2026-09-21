<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_updates') && !Schema::hasColumn('task_updates', 'next_action')) {
            Schema::table('task_updates', function (Blueprint $table) {
                $table->string('next_action')->nullable()->after('result');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_updates') && Schema::hasColumn('task_updates', 'next_action')) {
            Schema::table('task_updates', function (Blueprint $table) {
                $table->dropColumn('next_action');
            });
        }
    }
};
