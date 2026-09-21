<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tasks: add professional ERP fields
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks', 'estimated_duration_minutes')) {
                    $table->integer('estimated_duration_minutes')->nullable()->after('due_time');
                }
                if (!Schema::hasColumn('tasks', 'completion_what_was_done')) {
                    $table->text('completion_what_was_done')->nullable()->after('result');
                }
                if (!Schema::hasColumn('tasks', 'completion_issues')) {
                    $table->text('completion_issues')->nullable()->after('completion_what_was_done');
                }
                if (!Schema::hasColumn('tasks', 'completion_attachments')) {
                    $table->json('completion_attachments')->nullable()->after('completion_issues');
                }
                if (!Schema::hasColumn('tasks', 'declined_reason')) {
                    $table->text('declined_reason')->nullable()->after('completion_attachments');
                }
                if (!Schema::hasColumn('tasks', 'returned_reason')) {
                    $table->text('returned_reason')->nullable()->after('declined_reason');
                }
                if (!Schema::hasColumn('tasks', 'blocked_reason')) {
                    $table->text('blocked_reason')->nullable()->after('returned_reason');
                }
            });
        }

        // Watchers pivot
        if (!Schema::hasTable('task_watchers')) {
            Schema::create('task_watchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['task_id', 'user_id']);
            });
        }

        // Task templates: add checklist, attachments, supplier/asset/project generic relation
        if (Schema::hasTable('task_templates')) {
            Schema::table('task_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('task_templates', 'checklist_items')) {
                    $table->text('checklist_items')->nullable()->after('expected_outcome');
                }
                if (!Schema::hasColumn('task_templates', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('default_duration_days');
                }
            });
        }

        // Task updates: add structured completion report fields + blocked/waiting metadata
        if (Schema::hasTable('task_updates')) {
            Schema::table('task_updates', function (Blueprint $table) {
                if (!Schema::hasColumn('task_updates', 'what_was_done')) {
                    $table->text('what_was_done')->nullable()->after('comment');
                }
                if (!Schema::hasColumn('task_updates', 'issues_encountered')) {
                    $table->text('issues_encountered')->nullable()->after('what_was_done');
                }
                if (!Schema::hasColumn('task_updates', 'waiting_reason')) {
                    $table->string('waiting_reason')->nullable()->after('issues_encountered');
                }
            });
        }

        // Ensure task_templates has supplier/product generic support via extra columns if needed (already has task_type)
    }

    public function down(): void
    {
        if (Schema::hasTable('task_watchers')) {
            Schema::dropIfExists('task_watchers');
        }
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                foreach (['estimated_duration_minutes','completion_what_was_done','completion_issues','completion_attachments','declined_reason','returned_reason','blocked_reason'] as $col) {
                    if (Schema::hasColumn('tasks', $col)) {
                        try { $table->dropColumn($col); } catch (\Throwable $e) {}
                    }
                }
            });
        }
        if (Schema::hasTable('task_templates')) {
            Schema::table('task_templates', function (Blueprint $table) {
                foreach (['checklist_items','is_active'] as $col) {
                    if (Schema::hasColumn('tasks', $col)) {
                        try { $table->dropColumn($col); } catch (\Throwable $e) {}
                    }
                }
                // For templates table specifically
                if (Schema::hasColumn('task_templates', 'checklist_items')) {
                    try { $table->dropColumn('checklist_items'); } catch (\Throwable $e) {}
                }
                if (Schema::hasColumn('task_templates', 'is_active')) {
                    try { $table->dropColumn('is_active'); } catch (\Throwable $e) {}
                }
            });
        }
        if (Schema::hasTable('task_updates')) {
            Schema::table('task_updates', function (Blueprint $table) {
                foreach (['what_was_done','issues_encountered','waiting_reason'] as $col) {
                    if (Schema::hasColumn('task_updates', $col)) {
                        try { $table->dropColumn($col); } catch (\Throwable $e) {}
                    }
                }
            });
        }
    }
};
