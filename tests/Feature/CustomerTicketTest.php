<?php

namespace Tests\Feature;

use App\Models\ItTicket;
use App\Models\SaleInvoice;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use App\Services\ItService;
use Tests\TestCase;

class CustomerTicketTest extends TestCase
{
    public function test_create_individual_customer_ticket()
    {
        $this->withoutExceptionHandling();
        $user = User::firstOrCreate(
            ['email' => 'reception@jobarn.co.tz'],
            ['name' => 'Aisha Mwinyi', 'password' => bcrypt('12345678'), 'role' => 'reception', 'is_admin' => false]
        );
        $this->actingAs($user);

        $payload = [
            'customer_type' => 'individual',
            'full_name' => 'Emmanuel Mrema',
            'visitor_phone' => '+255755112233',
            'visitor_email' => 'emmanuel@example.com',
            'visit_purpose' => 'Technical Support',
            'customer_statement' => 'My laptop cannot turn on after power surge.',
            'forward_to_department' => 'IT Support',
            'handover_note' => 'Customer needs hardware diagnostic on laptop motherboard and power adapter.',
            'priority' => 'high',
        ];

        $response = $this->postJson(route('reception.api.tickets.store'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $json = $response->json();

        $this->assertStringStartsWith('TKT-', $json['ticket']['ticket_code']);
        $this->assertEquals('Emmanuel Mrema', $json['ticket']['customer']);
        $this->assertEquals('IT Support', $json['ticket']['forward_to']);
        $this->assertEquals('High', $json['ticket']['priority']);

        // Check DB Visit
        $visit = Visit::where('visitor', 'Emmanuel Mrema')->first();
        $this->assertNotNull($visit);
        $this->assertEquals('individual', $visit->customer_type);
        $this->assertEquals('Technical Support', $visit->purpose);
        $this->assertEquals('My laptop cannot turn on after power surge.', $visit->customer_statement);

        // Check DB ItTicket
        $itTicket = ItTicket::where('visit_id', $visit->id)->first();
        $this->assertNotNull($itTicket);
        $this->assertEquals('Emmanuel Mrema', $itTicket->visitor_name);
        $this->assertEquals('high', $itTicket->priority);

        // Check DB Task
        $task = Task::where('it_ticket_id', $itTicket->id)->first();
        $this->assertNotNull($task);
    }

    public function test_create_company_customer_ticket_with_progressive_fields()
    {
        $user = User::firstOrCreate(
            ['email' => 'reception@jobarn.co.tz'],
            ['name' => 'Aisha Mwinyi', 'password' => bcrypt('12345678'), 'role' => 'reception', 'is_admin' => false]
        );
        $this->actingAs($user);

        $payload = [
            'customer_type' => 'company',
            'organization_name' => 'ABC Technologies Ltd',
            'organization_type' => 'Private Company',
            'industry' => 'Information Technology',
            'tin_number' => '100-200-300',
            'contact_person' => 'John Michael',
            'contact_position' => 'IT Manager',
            'contact_phone' => '+255788990011',
            'contact_email' => 'john@company.co.tz',
            'region' => 'Dar es Salaam',
            'district' => 'Kinondoni',
            'physical_address' => 'Plot 14, Bagamoyo Road',
            'visit_purpose' => 'Technical Support',
            'customer_statement' => 'Our office computers require maintenance and antivirus renewal.',
            'forward_to_department' => 'IT Support',
            'handover_note' => 'Customer requires IT assistance to inspect and maintain their office computers and advise on the required solution.',
            'priority' => 'normal',
        ];

        $response = $this->postJson(route('reception.api.tickets.store'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $json = $response->json();

        $this->assertStringStartsWith('TKT-', $json['ticket']['ticket_code']);
        $this->assertEquals('ABC Technologies Ltd', $json['ticket']['customer']);
        $this->assertEquals('John Michael', $json['ticket']['contact_person']);
        $this->assertEquals('IT Manager', $json['ticket']['contact_position']);

        // Check DB Visit
        $visit = Visit::where('organization_name', 'ABC Technologies Ltd')->first();
        $this->assertNotNull($visit);
        $this->assertEquals('company', $visit->customer_type);
        $this->assertEquals('Private Company', $visit->organization_type);
        $this->assertEquals('John Michael', $visit->contact_person);
        $this->assertEquals('Kinondoni', $visit->district);

        // Check ItTicket
        $itTicket = ItTicket::where('visit_id', $visit->id)->first();
        $this->assertNotNull($itTicket);
        $this->assertEquals('ABC Technologies Ltd', $itTicket->visitor_name);
        $this->assertEquals('John Michael', $itTicket->contact_person);
        $this->assertEquals('IT Manager', $itTicket->contact_position);

        // IT Resolves ticket with price
        $itService = app(ItService::class);
        $resolved = $itService->resolveTicket($itTicket->id, 120000, 'All 5 office PCs serviced, malware cleaned.');
        $this->assertEquals(120000, $resolved['price']);
        $this->assertEquals('resolved', $resolved['status']);

        // Check auto-created Sale Invoice
        $invoice = SaleInvoice::where('it_ticket_id', $itTicket->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(120000, $invoice->amount);
        $this->assertEquals('pending_payment', $invoice->status);
    }

    protected function tearDown(): void
    {
        SaleInvoice::truncate();
        Task::truncate();
        ItTicket::truncate();
        Visit::truncate();
        if (file_exists(storage_path('app/it_portal.json'))) {
            unlink(storage_path('app/it_portal.json'));
        }
        parent::tearDown();
    }
}
