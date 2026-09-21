<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_products', 'supplier_id')) {
                    $table->foreignId('supplier_id')->nullable()->after('category')->constrained('suppliers')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_products')) {
            Schema::table('pos_products', function (Blueprint $table) {
                if (Schema::hasColumn('pos_products', 'supplier_id')) {
                    $table->dropConstrainedForeignId('supplier_id');
                }
            });
        }
    }
};
