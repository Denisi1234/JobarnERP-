








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
        // Create tasks first (no FK to it_tickets yet) to satisfy Postgres FK order
        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('priority')->default('medium');
                $table->string('status')->default('pending');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('it_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_name');
            $table->string('visitor_company')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->string('title')->default('PC Maintenance');
            $table->text('description')->nullable();
            $table->string('category')->default('PC Maintenance');
            $table->string('priority')->default('medium');
            $table->string('status')->default('pending');
            $table->integer('price')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Now add FKs after both tables exist
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'it_ticket_id')) {
                $table->foreignId('it_ticket_id')->nullable()->constrained('it_tickets')->nullOnDelete();
            }
        });
        Schema::table('it_tickets', function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
        });

        if (!Schema::hasTable('sale_invoices')) {
            Schema::create('sale_invoices', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('it_ticket_id')->nullable()->constrained('it_tickets')->nullOnDelete();
                $table->string('customer_name');
                $table->string('company')->nullable();
                $table->string('service')->default('PC Maintenance');
                $table->integer('amount'); // TZS
                $table->string('currency')->default('TZS');
                $table->string('status')->default('pending_payment'); // pending_payment, paid, cancelled
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete(); // sales person
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_tickets');
    }
};
