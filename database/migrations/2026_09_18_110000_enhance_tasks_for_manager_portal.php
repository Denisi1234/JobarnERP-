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
                if (!Schema::hasColumn('tasks', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'department_id')) {
                    $table->foreignId('department_id')->nullable()->after('created_by')->constrained('departments')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'category')) {
                    $table->string('category')->default('general')->after('priority');
                }
                if (!Schema::hasColumn('tasks', 'attachments')) {
                    $table->json('attachments')->nullable()->after('description');
                }
                if (!Schema::hasColumn('tasks', 'notes')) {
                    $table->text('notes')->nullable()->after('attachments');
                }
            });
        }

        if (!Schema::hasTable('task_comments')) {
            Schema::create('task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('body');
                $table->json('attachments')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_comments')) {
            Schema::dropIfExists('task_comments');
        }
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'created_by')) {
                    $table->dropConstrainedForeignId('created_by');
                }
                if (Schema::hasColumn('tasks', 'department_id')) {
                    $table->dropConstrainedForeignId('department_id');
                }
                foreach (['category','attachments','notes'] as $col) {
                    if (Schema::hasColumn('tasks', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
