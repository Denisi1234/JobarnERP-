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
                if (!Schema::hasColumn('tasks', 'instructions')) {
                    $table->text('instructions')->nullable()->after('description');
                }
                if (!Schema::hasColumn('tasks', 'expected_outcome')) {
                    $table->text('expected_outcome')->nullable()->after('instructions');
                }
                if (!Schema::hasColumn('tasks', 'start_date')) {
                    $table->date('start_date')->nullable()->after('due_at');
                }
                if (!Schema::hasColumn('tasks', 'due_time')) {
                    $table->time('due_time')->nullable()->after('due_at');
                }
            });
        }

        if (!Schema::hasTable('task_collaborators')) {
            Schema::create('task_collaborators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role')->default('collaborator');
                $table->timestamps();
                $table->unique(['task_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_collaborators')) {
            Schema::dropIfExists('task_collaborators');
        }
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                foreach (['instructions','expected_outcome','start_date','due_time'] as $col) {
                    if (Schema::hasColumn('tasks', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
