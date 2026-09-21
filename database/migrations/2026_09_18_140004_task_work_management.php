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
                if (!Schema::hasColumn('tasks', 'parent_id')) {
                    $table->foreignId('parent_id')->nullable()->after('it_ticket_id')->constrained('tasks')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'all_day')) {
                    $table->boolean('all_day')->default(false)->after('due_time');
                }
                if (!Schema::hasColumn('tasks', 'waiting_reason')) {
                    $table->string('waiting_reason')->nullable()->after('next_action');
                }
                if (!Schema::hasColumn('tasks', 'expected_resolution_at')) {
                    $table->date('expected_resolution_at')->nullable()->after('waiting_reason');
                }
                if (!Schema::hasColumn('tasks', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('completed_at');
                }
            });
        }

        if (!Schema::hasTable('task_checklists')) {
            Schema::create('task_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->string('title');
                $table->boolean('is_done')->default(false);
                $table->integer('position')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('task_dependencies')) {
            Schema::create('task_dependencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['task_id', 'depends_on_task_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_checklists');

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                foreach (['parent_id', 'all_day', 'waiting_reason', 'expected_resolution_at', 'submitted_at'] as $col) {
                    if (Schema::hasColumn('tasks', $col)) {
                        try { $table->dropColumn($col); } catch (\Throwable $e) {}
                    }
                }
            });
        }
    }
};
