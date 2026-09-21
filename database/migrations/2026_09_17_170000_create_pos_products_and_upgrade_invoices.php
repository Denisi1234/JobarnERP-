<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. POS Products / Catalog Table
        if (!Schema::hasTable('pos_products')) {
            Schema::create('pos_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('name');
                $table->string('sku')->unique()->index();
                $table->string('barcode')->nullable()->index();
                $table->string('category')->default('General');
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('cost_price', 14, 2)->nullable();
                $table->integer('stock_quantity')->nullable()->default(0);
                $table->boolean('is_service')->default(false);
                $table->string('icon')->default('inventory_2');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Upgrade Sale Invoices for full POS & Receipt capabilities
        if (Schema::hasTable('sale_invoices')) {
            Schema::table('sale_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_invoices', 'receipt_number')) {
                    $table->string('receipt_number')->nullable()->unique()->after('uuid');
                }
                if (!Schema::hasColumn('sale_invoices', 'customer_phone')) {
                    $table->string('customer_phone')->nullable()->after('customer_name');
                }
                if (!Schema::hasColumn('sale_invoices', 'subtotal')) {
                    $table->decimal('subtotal', 14, 2)->default(0)->after('amount');
                }
                if (!Schema::hasColumn('sale_invoices', 'tax_amount')) {
                    $table->decimal('tax_amount', 14, 2)->default(0)->after('subtotal');
                }
                if (!Schema::hasColumn('sale_invoices', 'discount_amount')) {
                    $table->decimal('discount_amount', 14, 2)->default(0)->after('tax_amount');
                }
                if (!Schema::hasColumn('sale_invoices', 'tendered_amount')) {
                    $table->decimal('tendered_amount', 14, 2)->default(0)->after('discount_amount');
                }
                if (!Schema::hasColumn('sale_invoices', 'change_amount')) {
                    $table->decimal('change_amount', 14, 2)->default(0)->after('tendered_amount');
                }
                if (!Schema::hasColumn('sale_invoices', 'payment_method')) {
                    $table->string('payment_method')->default('cash')->after('change_amount'); // cash, mpesa, card, bank_transfer
                }
                if (!Schema::hasColumn('sale_invoices', 'payment_reference')) {
                    $table->string('payment_reference')->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('sale_invoices', 'items_json')) {
                    $table->json('items_json')->nullable()->after('payment_reference');
                }
                if (!Schema::hasColumn('sale_invoices', 'notes')) {
                    $table->text('notes')->nullable()->after('items_json');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_products');
    }
};
