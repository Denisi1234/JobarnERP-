<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'product_kind')) {
                    $table->string('product_kind')->nullable()->after('address')->comment('Kind of product supplied: Hardware, Accessories, Software, etc.');
                }
                if (!Schema::hasColumn('suppliers', 'supply_categories')) {
                    $table->json('supply_categories')->nullable()->after('product_kind');
                }
                if (!Schema::hasColumn('suppliers', 'notes')) {
                    $table->text('notes')->nullable()->after('supply_categories');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (Schema::hasColumn('suppliers', 'product_kind')) {
                    $table->dropColumn('product_kind');
                }
                if (Schema::hasColumn('suppliers', 'supply_categories')) {
                    $table->dropColumn('supply_categories');
                }
                if (Schema::hasColumn('suppliers', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }
    }
};
