<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Leads Table
        if (!Schema::hasTable('sales_leads')) {
            Schema::create('sales_leads', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('company')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('source')->default('Walk-in'); // Walk-in, Phone, WhatsApp, Website, Referral
                $table->string('product_interest')->nullable();
                $table->string('status')->default('new'); // new, contacted, qualified, opportunity, won, lost
                $table->decimal('estimated_value', 14, 2)->default(0);
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('next_followup_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Opportunities (Pipeline) Table
        if (!Schema::hasTable('sales_opportunities')) {
            Schema::create('sales_opportunities', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('lead_id')->nullable()->constrained('sales_leads')->nullOnDelete();
                $table->string('customer_name');
                $table->string('company')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('title');
                $table->string('stage')->default('new'); // new, qualified, proposal, negotiation, won, lost
                $table->decimal('estimated_value', 14, 2)->default(0);
                $table->integer('probability')->default(20); // 0-100%
                $table->date('expected_close_date')->nullable();
                $table->string('next_action')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Quotations Table
        if (!Schema::hasTable('sales_quotations')) {
            Schema::create('sales_quotations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('quote_number')->unique();
                $table->foreignId('opportunity_id')->nullable()->constrained('sales_opportunities')->nullOnDelete();
                $table->string('customer_name');
                $table->string('company')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->string('status')->default('draft'); // draft, sent, accepted, rejected, expired
                $table->date('valid_until')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 4. Quotation Items Table
        if (!Schema::hasTable('sales_quotation_items')) {
            Schema::create('sales_quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained('sales_quotations')->cascadeOnDelete();
                $table->string('item_description');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('total_price', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        // 5. Activities & Follow-ups Table
        if (!Schema::hasTable('sales_activities')) {
            Schema::create('sales_activities', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('activity_type')->default('call'); // call, email, meeting, whatsapp, followup, task, note
                $table->string('customer_name');
                $table->string('company')->nullable();
                $table->string('subject');
                $table->text('notes')->nullable();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->string('status')->default('pending'); // pending, completed
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 6. Sales Targets Table
        if (!Schema::hasTable('sales_targets')) {
            Schema::create('sales_targets', function (Blueprint $table) {
                $table->id();
                $table->string('month_year'); // e.g. "2026-09"
                $table->decimal('target_amount', 14, 2)->default(5000000); // 5 Million TZS default monthly target
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
        Schema::dropIfExists('sales_activities');
        Schema::dropIfExists('sales_quotation_items');
        Schema::dropIfExists('sales_quotations');
        Schema::dropIfExists('sales_opportunities');
        Schema::dropIfExists('sales_leads');
    }
};
