<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_quotations', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(18.00)->after('subtotal');
            }
            if (!Schema::hasColumn('sales_quotations', 'tax_amount')) {
                $table->decimal('tax_amount', 14, 2)->default(0)->after('tax_rate');
            }
            if (!Schema::hasColumn('sales_quotations', 'shipping_amount')) {
                $table->decimal('shipping_amount', 14, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('sales_quotations', 'payment_terms')) {
                $table->string('payment_terms')->default('Net 30 Days')->after('status');
            }
            if (!Schema::hasColumn('sales_quotations', 'billing_address')) {
                $table->text('billing_address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('sales_quotations', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('billing_address');
            }
            if (!Schema::hasColumn('sales_quotations', 'terms_conditions')) {
                $table->text('terms_conditions')->nullable()->after('notes');
            }
        });

        Schema::table('sales_quotation_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_quotation_items', 'item_name')) {
                $table->string('item_name')->nullable()->after('quotation_id');
            }
            if (!Schema::hasColumn('sales_quotation_items', 'discount_rate')) {
                $table->decimal('discount_rate', 5, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('sales_quotation_items', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(18.00)->after('discount_rate');
            }
        });

        Schema::table('sales_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_leads', 'title')) {
                $table->string('title')->nullable()->after('name'); // Job Title
            }
            if (!Schema::hasColumn('sales_leads', 'department')) {
                $table->string('department')->nullable()->after('company');
            }
            if (!Schema::hasColumn('sales_leads', 'address')) {
                $table->text('address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('sales_leads', 'converted_at')) {
                $table->dateTime('converted_at')->nullable()->after('status');
            }
        });

        Schema::table('sales_opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_opportunities', 'lead_source')) {
                $table->string('lead_source')->default('Direct')->after('title');
            }
            if (!Schema::hasColumn('sales_opportunities', 'opportunity_type')) {
                $table->string('opportunity_type')->default('New Business')->after('lead_source');
            }
        });
    }

    public function down(): void
    {
        // Safe reversible migration
    }
};
