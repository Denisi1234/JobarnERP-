<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tasks') && !Schema::hasColumn('tasks', 'overdue_notified_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->timestamp('overdue_notified_at')->nullable()->after('verified_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks', 'overdue_notified_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('overdue_notified_at');
            });
        }
    }
};
