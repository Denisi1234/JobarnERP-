<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('debits')) {
            Schema::create('debits', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('type')->default('supplier'); // supplier (payable) | customer (receivable)
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->foreignId('pos_product_id')->nullable()->constrained('pos_products')->nullOnDelete();
                $table->foreignId('sale_invoice_id')->nullable()->constrained('sale_invoices')->nullOnDelete();
                $table->foreignId('inventory_log_id')->nullable()->constrained('inventory_logs')->nullOnDelete();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('status')->default('pending'); // pending, paid, overdue
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debits');
    }
};
