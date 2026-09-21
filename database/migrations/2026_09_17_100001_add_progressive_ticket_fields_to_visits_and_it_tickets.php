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
        Schema::table('visits', function (Blueprint $table) {
            if (!Schema::hasColumn('visits', 'ticket_code')) {
                $table->string('ticket_code')->nullable()->unique()->after('uuid');
            }
            if (!Schema::hasColumn('visits', 'customer_type')) {
                $table->string('customer_type')->default('individual')->after('ticket_code');
            }
            if (!Schema::hasColumn('visits', 'organization_name')) {
                $table->string('organization_name')->nullable()->after('company');
            }
            if (!Schema::hasColumn('visits', 'organization_type')) {
                $table->string('organization_type')->nullable()->after('organization_name');
            }
            if (!Schema::hasColumn('visits', 'industry')) {
                $table->string('industry')->nullable()->after('organization_type');
            }
            if (!Schema::hasColumn('visits', 'tin_number')) {
                $table->string('tin_number')->nullable()->after('industry');
            }
            if (!Schema::hasColumn('visits', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('tin_number');
            }
            if (!Schema::hasColumn('visits', 'contact_position')) {
                $table->string('contact_position')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('visits', 'region')) {
                $table->string('region')->nullable()->after('contact_position');
            }
            if (!Schema::hasColumn('visits', 'district')) {
                $table->string('district')->nullable()->after('region');
            }
            if (!Schema::hasColumn('visits', 'physical_address')) {
                $table->text('physical_address')->nullable()->after('district');
            }
            if (!Schema::hasColumn('visits', 'customer_statement')) {
                $table->text('customer_statement')->nullable()->after('purpose');
            }
            if (!Schema::hasColumn('visits', 'forward_to_department')) {
                $table->string('forward_to_department')->nullable()->after('current_department');
            }
            if (!Schema::hasColumn('visits', 'handover_note')) {
                $table->text('handover_note')->nullable()->after('customer_statement');
            }
            if (!Schema::hasColumn('visits', 'priority')) {
                $table->string('priority')->default('normal')->after('handover_note');
            }
        });

        Schema::table('it_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('it_tickets', 'ticket_code')) {
                $table->string('ticket_code')->nullable()->after('uuid');
            }
            if (!Schema::hasColumn('it_tickets', 'customer_type')) {
                $table->string('customer_type')->default('individual')->after('ticket_code');
            }
            if (!Schema::hasColumn('it_tickets', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('visitor_name');
            }
            if (!Schema::hasColumn('it_tickets', 'contact_position')) {
                $table->string('contact_position')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('it_tickets', 'customer_statement')) {
                $table->text('customer_statement')->nullable()->after('description');
            }
            if (!Schema::hasColumn('it_tickets', 'handover_note')) {
                $table->text('handover_note')->nullable()->after('customer_statement');
            }
            if (!Schema::hasColumn('it_tickets', 'organization_type')) {
                $table->string('organization_type')->nullable()->after('visitor_company');
            }
            if (!Schema::hasColumn('it_tickets', 'industry')) {
                $table->string('industry')->nullable()->after('organization_type');
            }
            if (!Schema::hasColumn('it_tickets', 'region')) {
                $table->string('region')->nullable()->after('location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_code',
                'customer_type',
                'organization_name',
                'organization_type',
                'industry',
                'tin_number',
                'contact_person',
                'contact_position',
                'region',
                'district',
                'physical_address',
                'customer_statement',
                'forward_to_department',
                'handover_note',
                'priority',
            ]);
        });

        Schema::table('it_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_code',
                'customer_type',
                'contact_person',
                'contact_position',
                'customer_statement',
                'handover_note',
                'organization_type',
                'industry',
                'region',
            ]);
        });
    }
};
