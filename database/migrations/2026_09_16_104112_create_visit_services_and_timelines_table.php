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
        // Add status and financial columns to visits table if not exist
        Schema::table('visits', function (Blueprint $table) {
            if (!Schema::hasColumn('visits', 'company')) {
                $table->string('company')->nullable()->after('visitor');
            }
            if (!Schema::hasColumn('visits', 'status')) {
                $table->string('status')->default('checked_in')->after('purpose'); // checked_in, in_progress, ready_for_billing, completed, checked_out
            }
            if (!Schema::hasColumn('visits', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('status'); // pending, paid, credit
            }
            if (!Schema::hasColumn('visits', 'total_amount')) {
                $table->integer('total_amount')->default(0)->after('payment_status'); // TZS
            }
            if (!Schema::hasColumn('visits', 'current_department')) {
                $table->string('current_department')->default('reception')->after('total_amount'); // reception, it, sales, completed
            }
            if (!Schema::hasColumn('visits', 'checkout_notes')) {
                $table->text('checkout_notes')->nullable()->after('departure');
            }
        });

        // Create visit_services table for multi-service per single visit
        if (!Schema::hasTable('visit_services')) {
            Schema::create('visit_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->string('department'); // 'IT', 'SALES', 'ACCOUNTS', etc.
                $table->string('service_name')->default('Support / Service');
                $table->text('request_description')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->integer('price')->default(0); // TZS
                $table->integer('quantity')->default(1);
                $table->string('status')->default('pending'); // pending, accepted, in_progress, completed, cancelled
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // Create visit_timelines table for real-time activity log
        if (!Schema::hasTable('visit_timelines')) {
            Schema::create('visit_timelines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type'); // check_in, handoff, accepted, in_progress, price_added, completed, checkout
                $table->string('department')->nullable(); // reception, it, sales
                $table->string('title');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_timelines');
        Schema::dropIfExists('visit_services');
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['company', 'status', 'payment_status', 'total_amount', 'current_department', 'checkout_notes']);
        });
    }
};
