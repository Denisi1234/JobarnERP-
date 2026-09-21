<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Warehouses
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('location')->nullable();
                $table->string('type')->default('store'); // store, main, transit, repair
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Enhance pos_products for IT electronics
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_products', 'brand')) $table->string('brand')->nullable()->after('name');
                if (!Schema::hasColumn('pos_products', 'model')) $table->string('model')->nullable()->after('brand');
                if (!Schema::hasColumn('pos_products', 'description')) $table->text('description')->nullable()->after('model');
                if (!Schema::hasColumn('pos_products', 'image_path')) $table->string('image_path')->nullable()->after('description');
                if (!Schema::hasColumn('pos_products', 'specs')) $table->json('specs')->nullable()->after('image_path'); // structured attributes
                if (!Schema::hasColumn('pos_products', 'tracking_method')) $table->string('tracking_method')->default('quantity')->after('specs'); // none, quantity, serial, batch
                if (!Schema::hasColumn('pos_products', 'min_stock_alert')) $table->integer('min_stock_alert')->default(5)->after('stock_quantity');
                if (!Schema::hasColumn('pos_products', 'warehouse_id')) $table->foreignId('warehouse_id')->nullable()->after('supplier_id')->constrained('warehouses')->nullOnDelete();
            });
        }

        // 3. Serialized inventory units — one row per physical device
        if (!Schema::hasTable('inventory_units')) {
            Schema::create('inventory_units', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('pos_product_id')->constrained('pos_products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->string('serial_number')->unique();
                $table->string('barcode')->nullable();
                $table->string('status')->default('available'); // available, reserved, sold, delivered, under_repair, returned, damaged, defective, in_transit, awaiting_inspection
                $table->decimal('cost', 14, 2)->nullable();
                $table->decimal('selling_price', 14, 2)->nullable();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('purchase_order')->nullable();
                $table->string('purchase_invoice')->nullable();
                $table->date('purchase_date')->nullable();
                $table->string('warranty_provider')->nullable();
                $table->string('warranty_type')->nullable(); // manufacturer, supplier, company
                $table->date('warranty_start')->nullable();
                $table->date('warranty_end')->nullable();
                $table->string('warranty_status')->default('active'); // active, expired, claim_in_progress, voided
                $table->foreignId('customer_id')->nullable()->constrained('sales_leads')->nullOnDelete();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->foreignId('invoice_id')->nullable()->constrained('sale_invoices')->nullOnDelete();
                $table->string('reserved_for')->nullable();
                $table->date('reserved_until')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'warehouse_id']);
            });
        }

        // 4. Warranty claims — links to serial
        if (!Schema::hasTable('warranty_claims')) {
            Schema::create('warranty_claims', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('inventory_unit_id')->constrained('inventory_units')->cascadeOnDelete();
                $table->foreignId('pos_product_id')->constrained('pos_products')->cascadeOnDelete();
                $table->string('customer_name');
                $table->string('customer_phone')->nullable();
                $table->string('serial_number');
                $table->text('issue_description');
                $table->string('status')->default('received'); // received, inspection, diagnosis, repair, supplier_claim, resolved, returned
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->text('resolution_notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 5. Ensure warehouses seeded
        try {
            if (Schema::hasTable('warehouses') && \App\Models\Warehouse::count() === 0) {
                // will be seeded via controller
            }
        } catch (\Throwable $e) {}

        // 6. Inventory logs enhancement for serial tracking (reference_serial)
        if (Schema::hasTable('inventory_logs')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_logs', 'warehouse_id')) $table->foreignId('warehouse_id')->nullable()->after('pos_product_id')->constrained('warehouses')->nullOnDelete();
                if (!Schema::hasColumn('inventory_logs', 'serial_number')) $table->string('serial_number')->nullable()->after('warehouse_id');
                if (!Schema::hasColumn('inventory_logs', 'cost')) $table->decimal('cost', 14, 2)->nullable()->after('serial_number');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
        Schema::dropIfExists('inventory_units');
        if (Schema::hasTable('inventory_logs')) {
            Schema::table('inventory_logs', function (Blueprint $table) {
                if (Schema::hasColumn('inventory_logs', 'warehouse_id')) $table->dropConstrainedForeignId('warehouse_id');
                foreach (['serial_number','cost'] as $col) if (Schema::hasColumn('inventory_logs', $col)) $table->dropColumn($col);
            });
        }
        Schema::dropIfExists('warehouses');
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                $drop = ['brand','model','description','image_path','specs','tracking_method','min_stock_alert','warehouse_id'];
                foreach ($drop as $col) if (Schema::hasColumn('pos_products', $col)) { try { $table->dropColumn($col); } catch (\Throwable $e) {} }
            });
        }
    }
};
