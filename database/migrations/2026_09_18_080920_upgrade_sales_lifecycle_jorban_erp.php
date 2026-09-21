<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Upgrade sales_quotations for full lifecycle & electronics/ERP workflow
        Schema::table('sales_quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_quotations', 'type')) {
                // type: quotation, proforma
                $table->string('type')->default('quotation')->after('uuid');
            }
            if (!Schema::hasColumn('sales_quotations', 'proforma_number')) {
                $table->string('proforma_number')->nullable()->after('quote_number')->index();
            }
            if (!Schema::hasColumn('sales_quotations', 'sales_order_number')) {
                $table->string('sales_order_number')->nullable()->after('proforma_number')->index();
            }
            if (!Schema::hasColumn('sales_quotations', 'customer_tin')) {
                $table->string('customer_tin')->nullable()->after('company');
            }
            if (!Schema::hasColumn('sales_quotations', 'currency')) {
                $table->string('currency', 10)->default('TZS')->after('discount');
            }
            if (!Schema::hasColumn('sales_quotations', 'price_list')) {
                $table->string('price_list')->default('Standard Retail')->after('currency');
            }
            if (!Schema::hasColumn('sales_quotations', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('sales_quotations', 'parent_quotation_id')) {
                $table->foreignId('parent_quotation_id')->nullable()->after('opportunity_id')->constrained('sales_quotations')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_quotations', 'timeline_events')) {
                $table->json('timeline_events')->nullable()->after('terms_conditions');
            }
        });

        // 2. Upgrade sales_quotation_items for computer/electronics hardware specs
        Schema::table('sales_quotation_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_quotation_items', 'pos_product_id')) {
                $table->foreignId('pos_product_id')->nullable()->after('quotation_id')->constrained('pos_products')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_quotation_items', 'sku')) {
                $table->string('sku')->nullable()->after('pos_product_id');
            }
            if (!Schema::hasColumn('sales_quotation_items', 'model_specs')) {
                // e.g. "16GB/512GB SSD / FHD"
                $table->string('model_specs')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('sales_quotation_items', 'discount_amount')) {
                $table->decimal('discount_amount', 14, 2)->default(0)->after('discount_rate');
            }
            if (!Schema::hasColumn('sales_quotation_items', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)->default(0)->after('tax_rate');
            }
        });

        // 3. Upgrade sale_invoices to link back to quotation/proforma/sales order
        Schema::table('sale_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_invoices', 'sales_quotation_id')) {
                $table->foreignId('sales_quotation_id')->nullable()->after('uuid')->constrained('sales_quotations')->nullOnDelete();
            }
            if (!Schema::hasColumn('sale_invoices', 'source_document')) {
                $table->string('source_document')->nullable()->after('sales_quotation_id');
            }
            if (!Schema::hasColumn('sale_invoices', 'customer_tin')) {
                $table->string('customer_tin')->nullable()->after('customer_phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sale_invoices', 'sales_quotation_id')) {
                $table->dropConstrainedForeignId('sales_quotation_id');
            }
            if (Schema::hasColumn('sale_invoices', 'source_document')) $table->dropColumn('source_document');
            if (Schema::hasColumn('sale_invoices', 'customer_tin')) $table->dropColumn('customer_tin');
        });

        Schema::table('sales_quotation_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_quotation_items', 'pos_product_id')) {
                $table->dropConstrainedForeignId('pos_product_id');
            }
            $cols = ['sku', 'model_specs', 'discount_amount', 'tax_amount'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('sales_quotation_items', $c)) $table->dropColumn($c);
            }
        });

        Schema::table('sales_quotations', function (Blueprint $table) {
            if (Schema::hasColumn('sales_quotations', 'parent_quotation_id')) {
                $table->dropConstrainedForeignId('parent_quotation_id');
            }
            $cols = ['type', 'proforma_number', 'sales_order_number', 'customer_tin', 'currency', 'price_list', 'customer_notes', 'timeline_events'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('sales_quotations', $c)) $table->dropColumn($c);
            }
        });
    }
};
