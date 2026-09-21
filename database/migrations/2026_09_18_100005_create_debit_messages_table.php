<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('debit_messages')) {
            Schema::create('debit_messages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('debit_id')->constrained('debits')->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('customer_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('message');
                $table->string('channel')->default('sms');
                $table->string('status')->default('sent');
                $table->text('provider_response')->nullable();
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_messages');
    }
};
