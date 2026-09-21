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
                if (!Schema::hasColumn('tasks', 'task_type')) {
                    $table->string('task_type')->default('general')->after('category');
                }
                if (!Schema::hasColumn('tasks', 'department')) {
                    $table->string('department')->default('general')->after('task_type');
                }
                if (!Schema::hasColumn('tasks', 'customer_name')) {
                    $table->string('customer_name')->nullable()->after('department');
                }
                if (!Schema::hasColumn('tasks', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->after('customer_name');
                }
                if (!Schema::hasColumn('tasks', 'lead_id')) {
                    $table->foreignId('lead_id')->nullable()->after('customer_id')->constrained('sales_leads')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'quotation_id')) {
                    $table->foreignId('quotation_id')->nullable()->after('lead_id')->constrained('sales_quotations')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'sales_order_id')) {
                    $table->foreignId('sales_order_id')->nullable()->after('quotation_id')->constrained('sale_invoices')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'product_id')) {
                    $table->foreignId('product_id')->nullable()->after('sales_order_id')->constrained('pos_products')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('tasks', 'verified_by')) {
                    $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('tasks', 'is_recurring')) {
                    $table->boolean('is_recurring')->default(false)->after('verified_by');
                }
                if (!Schema::hasColumn('tasks', 'repeat_interval')) {
                    $table->string('repeat_interval')->nullable()->after('is_recurring'); // daily, weekly, monthly
                }
                if (!Schema::hasColumn('tasks', 'repeat_until')) {
                    $table->date('repeat_until')->nullable()->after('repeat_interval');
                }
                if (!Schema::hasColumn('tasks', 'progress')) {
                    $table->integer('progress')->default(0)->after('repeat_until');
                }
                if (!Schema::hasColumn('tasks', 'result')) {
                    $table->text('result')->nullable()->after('progress');
                }
                if (!Schema::hasColumn('tasks', 'next_action')) {
                    $table->string('next_action')->nullable()->after('result');
                }
            });
        }

        // Task updates for audit trail
        if (!Schema::hasTable('task_updates')) {
            Schema::create('task_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status_from')->nullable();
                $table->string('status_to');
                $table->text('comment')->nullable();
                $table->string('action_taken')->nullable();
                $table->string('result')->nullable();
                $table->json('attachments')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_updates')) {
            Schema::dropIfExists('task_updates');
        }
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                foreach (['task_type','department','customer_name','customer_id','lead_id','quotation_id','sales_order_id','product_id','verified_at','verified_by','is_recurring','repeat_interval','repeat_until','progress','result','next_action'] as $col) {
                    if (Schema::hasColumn('tasks', $col)) {
                        try { $table->dropColumn($col); } catch (\Throwable $e) {}
                    }
                }
            });
        }
    }
};
