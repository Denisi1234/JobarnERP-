<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('task_templates')) {
            Schema::create('task_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->text('instructions')->nullable();
                $table->text('expected_outcome')->nullable();
                $table->string('task_type')->default('general');
                $table->string('department')->default('general');
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('priority')->default('medium');
                $table->integer('default_duration_days')->default(1);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
