<?php

namespace Tests\Feature;

use App\Models\ItTicket;
use App\Models\SaleInvoice;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use App\Services\ItService;
use Tests\TestCase;

class PortalWorkflowTest extends TestCase
{
    public function test_post_login_authenticates_and_redirects()
    {
        $user = User::firstOrCreate(
            ['email' => 'reception@jobarn.co.tz'],
            ['name' => 'Aisha Mwinyi', 'password' => bcrypt('12345678'), 'role' => 'reception', 'is_admin' => false]
        );
        $user->update(['is_admin' => false, 'role' => 'reception']);

        // Test POST /login fallback
        $response = $this->post('/login', [
            'email' => 'reception@jobarn.co.tz',
            'password' => '12345678',
            'role' => 'reception',
        ]);

        $response->assertRedirect('/reception');
        $this->assertAuthenticatedAs($user);
    }

    public function test_end_to_end_reception_to_it_to_sales_workflow()
    {
        $receptionUser = User::where('email', 'reception@jobarn.co.tz')->first();
        $this->actingAs($receptionUser);

        // 1. Reception Check-In
        $checkinResponse = $this->postJson(route('reception.api.checkin'), [
            'visitor' => 'Hassani Ally',
            'visitor_phone' => '+255712999888',
            'visitor_company' => 'Airtel Tanzania',
            'host' => 'Aisha Mwinyi',
            'purpose' => 'PC Maintenance — Blue Screen Crash',
        ]);

        $checkinResponse->assertJson(['success' => true]);
        $visit = Visit::where('visitor', 'Hassani Ally')->latest('id')->first();
        $this->assertNotNull($visit);

        // 2. Forward from Reception to IT
        $itService = app(ItService::class);
        $ticketData = [
            'visit_id' => $visit->id,
            'visitor_name' => $visit->visitor,
            'visitor_company' => 'Airtel Tanzania',
            'visitor_phone' => $visit->visitor_phone,
            'title' => 'PC Maintenance — BSOD Repair',
            'description' => 'Blue screen on boot, RAM reseat and driver cleanup needed',
            'category' => 'PC Maintenance',
            'priority' => 'high',
            'location' => 'Lobby 1',
        ];

        $ticket = $itService->createTicket($ticketData);
        $this->assertNotNull($ticket['id']);

        $dbTicket = ItTicket::find($ticket['id']);
        $this->assertEquals($visit->id, $dbTicket->visit_id);
        $this->assertEquals('pending', $dbTicket->status);

        // Linked task must exist
        $task = Task::where('it_ticket_id', $dbTicket->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals('pending', $task->status);

        // 3. IT Assigns Ticket
        $itUser = User::where('role', 'it')->first();
        $assigned = $itService->assignTicket($dbTicket->id, $itUser?->id ?? 'Rajabu Simba');
        $this->assertContains($assigned['status'], ['assigned', 'accepted']);
        $this->assertEquals('in_progress', $task->fresh()->status);

        // 4. IT Resolves Ticket with Price (TZS 75,000)
        $resolved = $itService->resolveTicket($dbTicket->id, 75000, 'Replaced bad RAM stick, thermal paste renewed.');
        $this->assertEquals('resolved', $resolved['status']);
        $this->assertEquals(75000, $resolved['price']);
        $this->assertEquals('completed', $task->fresh()->status);

        // 5. Auto Sale Invoice generated
        $invoice = SaleInvoice::where('it_ticket_id', $dbTicket->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('pending_payment', $invoice->status);
        $this->assertEquals(75000, $invoice->amount);
        $this->assertEquals('Hassani Ally', $invoice->customer_name);

        // 6. Sales collects payment (Take Money)
        $paid = $itService->payInvoice($invoice->id);
        $this->assertEquals('paid', $paid['status']);
        $this->assertNotNull($paid['paid_at']);
    }

    public function test_all_portal_pages_and_exports_render_successfully()
    {
        // Reception portal — HARDENED role:reception
        $reception = User::where('email', 'reception@jobarn.co.tz')->first() ?? User::factory()->create(['role'=>'reception']);
        $this->actingAs($reception);
        $this->get(route('reception.dashboard'))->assertOk();
        $this->get(route('reception.visitors'))->assertOk();
        $this->get(route('reception.visitors.export'))->assertOk();
        $this->get('/reception/appointments')->assertNotFound();
        $this->get(route('reception.directory'))->assertOk();
        $this->get(route('reception.tasks'))->assertOk();
        $this->get(route('reception.tasks.export'))->assertOk();
        $this->get(route('reception.deliveries'))->assertOk();
        $this->get(route('reception.settings'))->assertOk();

        // IT Portal — role:it
        $itUser = User::where('role','it')->first() ?? User::factory()->create(['role'=>'it']);
        $this->actingAs($itUser);
        $this->get(route('it.index'))->assertOk();
        $this->get(route('it.tickets.export'))->assertOk();

        // Sales Portal — role:sales
        $salesUser = User::where('role','sales')->first() ?? User::factory()->create(['role'=>'sales']);
        $this->actingAs($salesUser);
        $this->get(route('sales.index'))->assertOk();
        $this->get(route('sales.index', ['tab' => 'leads']))->assertOk();
        $this->get(route('sales.index', ['tab' => 'pipeline']))->assertOk();
        $this->get(route('sales.index', ['tab' => 'quotations']))->assertOk();
        $this->get(route('sales.index', ['tab' => 'orders']))->assertOk();
        $this->get(route('sales.index', ['tab' => 'activities']))->assertOk();
        $this->get(route('sales.index', ['tab' => 'reports']))->assertOk();
        $this->get(route('sales.invoices.export'))->assertOk();

        // Manager Portal — role:manager
        $manager = User::where('role','manager')->first() ?? User::factory()->create(['role'=>'manager']);
        $this->actingAs($manager);
        $this->get(route('manager.index'))->assertOk();
        $this->get(route('manager.export'))->assertOk();

        // Cross-portal isolation — HARDENED: reception cannot access IT
        $this->actingAs($reception);
        $this->get(route('it.index'))->assertStatus(403);
        $this->get(route('sales.index'))->assertStatus(403);
    }

    public function test_sales_crm_lead_to_quote_to_order_workflow()
    {
        $this->withoutExceptionHandling();
        $salesUser = User::where('role', 'sales')->first() ?? User::where('email', 'reception@jobarn.co.tz')->first();
        $this->actingAs($salesUser);

        // 1. Create Lead
        $leadResp = $this->post(route('sales.leads.store'), [
            'name' => 'Baraka Ally',
            'company' => 'Airtel TZ',
            'phone' => '+255711223344',
            'product_interest' => '5 Refurbished ThinkPads',
            'estimated_value' => 3500000,
        ]);
        $leadResp->assertRedirect(route('sales.index', ['tab' => 'leads']));

        $lead = \App\Models\SalesLead::where('name', 'Baraka Ally')->first();
        $this->assertNotNull($lead);

        // 2. Qualify Lead -> Auto-creates Opportunity
        $this->post(route('sales.leads.status', $lead->id), ['status' => 'qualified']);
        $opp = \App\Models\SalesOpportunity::where('lead_id', $lead->id)->first();
        $this->assertNotNull($opp);
        $this->assertEquals('qualified', $opp->stage);

        // 3. Create Quotation
        $quoteResp = $this->post(route('sales.quotations.store'), [
            'customer_name' => 'Baraka Ally',
            'company' => 'Airtel TZ',
            'item_description' => 'ThinkPad T480 Core i5 8GB 256GB SSD',
            'quantity' => 5,
            'unit_price' => 700000,
            'discount' => 100000,
        ]);
        $quoteResp->assertRedirect(route('sales.index', ['tab' => 'quotations']));

        $quote = \App\Models\SalesQuotation::where('customer_name', 'Baraka Ally')->first();
        $this->assertNotNull($quote);
        $this->assertEquals(3400000, $quote->total_amount);

        // 4. Accept Quotation -> Generates Sales Order (SaleInvoice)
        $acceptResp = $this->post(route('sales.quotations.accept', $quote->id));
        $acceptResp->assertRedirect(route('sales.index', ['tab' => 'orders']));

        $invoice = SaleInvoice::where('customer_name', 'Baraka Ally')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(3400000, $invoice->amount);
        $this->assertEquals('pending_payment', $invoice->status);

        // 5. Pay / Settle Order
        $this->post(route('sales.invoices.pay', $invoice->id));
        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    protected function tearDown(): void
    {
        \App\Models\SalesActivity::truncate();
        \App\Models\SalesQuotationItem::truncate();
        \App\Models\SalesQuotation::truncate();
        \App\Models\SalesOpportunity::truncate();
        \App\Models\SalesLead::truncate();
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
