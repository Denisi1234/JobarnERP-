<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('work_reports', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('report_date'); $table->time('entry_time')->nullable(); $table->time('out_time')->nullable(); $table->string('report_type')->default('daily'); $table->text('work_completed'); $table->text('activity_performed')->nullable(); $table->text('outcomes')->nullable(); $table->text('blockers')->nullable(); $table->text('next_actions')->nullable();
            $table->string('status')->default('submitted')->index(); $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('manager_comment')->nullable(); $table->timestamp('reviewed_at')->nullable(); $table->timestamps();
            $table->unique(['user_id', 'report_date', 'report_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('work_reports'); }
};
