<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_budgets', function (Blueprint $table) {
            $table->id(); $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7); $table->decimal('amount', 14, 2); $table->decimal('committed_amount', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['department_id', 'period']);
        });
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id(); $table->string('po_number')->unique(); $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete(); $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('draft')->index(); $table->date('expected_on')->nullable(); $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('approved_at')->nullable(); $table->timestamps();
        });
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('pos_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description'); $table->integer('quantity')->default(1); $table->decimal('unit_cost', 14, 2)->default(0); $table->timestamps();
        });
        Schema::create('staff_requests', function (Blueprint $table) {
            $table->id(); $table->string('request_type'); $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title'); $table->decimal('amount', 14, 2)->nullable(); $table->date('start_date')->nullable(); $table->date('end_date')->nullable(); $table->text('reason');
            $table->string('status')->default('pending')->index(); $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('decision_comment')->nullable(); $table->timestamp('decided_at')->nullable(); $table->timestamps();
        });
        Schema::create('manager_report_schedules', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('frequency'); $table->string('recipient_email'); $table->json('sections')->nullable(); $table->boolean('active')->default(true); $table->timestamp('last_sent_at')->nullable(); $table->timestamp('next_run_at')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('manager_report_schedules'); Schema::dropIfExists('staff_requests'); Schema::dropIfExists('purchase_order_items'); Schema::dropIfExists('purchase_orders'); Schema::dropIfExists('department_budgets'); }
};
