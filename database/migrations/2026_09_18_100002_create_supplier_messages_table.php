<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_messages')) {
            Schema::create('supplier_messages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('message');
                $table->string('channel')->default('sms'); // sms, email, both
                $table->string('status')->default('sent'); // sent, failed, log_only
                $table->text('provider_response')->nullable();
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_messages');
    }
};
