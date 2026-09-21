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
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_products', 'min_stock_alert')) {
                    $table->integer('min_stock_alert')->default(5)->after('stock_quantity');
                }
                if (!Schema::hasColumn('pos_products', 'description')) {
                    $table->text('description')->nullable()->after('category');
                }
            });
        }

        if (!Schema::hasTable('inventory_logs')) {
            Schema::create('inventory_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_product_id')->constrained('pos_products')->cascadeOnDelete();
                $table->string('type')->default('adjustment'); // stock_in, stock_out, sale, adjustment, initial
                $table->integer('quantity_change'); // +10 or -5
                $table->integer('quantity_before');
                $table->integer('quantity_after');
                $table->string('reason')->nullable(); // Supplier Delivery, POS Sale, Damaged, Recount
                $table->string('reference')->nullable(); // Invoice # or PO #
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
