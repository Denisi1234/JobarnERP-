<?php

namespace Tests\Feature;

use App\Models\SaleInvoice;
use App\Models\SalesActivity;
use App\Models\SalesLead;
use App\Models\SalesOpportunity;
use App\Models\SalesQuotation;
use App\Models\User;
use Tests\TestCase;

class SuiteCrmSalesEnterpriseTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::firstOrCreate(
            ['email' => 'sales_suitecrm@jobarn.co.tz'],
            ['name' => 'SuiteCRM Sales Agent', 'password' => bcrypt('12345678'), 'role' => 'sales']
        );
    }

    public function test_suitecrm_multi_line_quotation_creation_and_calculation(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('sales.quotations.store'), [
            'customer_name' => 'Dr. Frank Mwambola',
            'company' => 'Aga Khan Hospital',
            'phone' => '+255 788 123 456',
            'email' => 'frank@agakhan.org',
            'tax_rate' => 18,
            'shipping_amount' => 50000,
            'payment_terms' => 'Net 30 Days',
            'items' => [
                [
                    'item_name' => 'Core Network Switch 48-Port',
                    'item_description' => 'Cisco Catalyst Managed Switch',
                    'quantity' => 2,
                    'unit_price' => 1500000,
                    'discount_rate' => 10, // 10% disc on 3,000,000 = 2,700,000
                    'tax_rate' => 18,
                ],
                [
                    'item_name' => 'Rack Mount Installation Service',
                    'item_description' => 'On-site server rack setup',
                    'quantity' => 1,
                    'unit_price' => 300000,
                    'discount_rate' => 0,
                    'tax_rate' => 18,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.index', ['tab' => 'quotations']));

        $quote = SalesQuotation::with('items')->where('customer_name', 'Dr. Frank Mwambola')->first();
        $this->assertNotNull($quote);
        $this->assertCount(2, $quote->items);
        $this->assertEquals(3300000, $quote->subtotal);
        $this->assertEquals(300000, $quote->discount);
        // Taxable = 3,000,000; Tax 18% = 540,000; Shipping = 50,000; Grand Total = 3,590,000
        $this->assertEquals(540000, $quote->tax_amount);
        $this->assertEquals(3590000, $quote->total_amount);

        // Test Print View
        $printResp = $this->get(route('sales.quotations.print', $quote->id));
        $printResp->assertStatus(200);
        $printResp->assertSee('Core Network Switch 48-Port');
        $printResp->assertSee('3,590,000');
    }

    public function test_suitecrm_1_click_lead_conversion_creates_opportunity_and_activity(): void
    {
        $this->actingAs($this->user);

        $lead = SalesLead::create([
            'name' => 'Neema Shirima',
            'company' => 'CRDB Bank Head Office',
            'phone' => '+255 754 999 888',
            'email' => 'neema.shirima@crdb.co.tz',
            'source' => 'Website',
            'product_interest' => 'Enterprise Cloud Migration',
            'estimated_value' => 12000000,
            'status' => 'new',
            'assigned_to' => $this->user->id,
        ]);

        $response = $this->post(route('sales.leads.convert', $lead->id), [
            'deal_title' => 'CRDB Cloud Infrastructure Overhaul',
            'estimated_value' => 15000000,
            'expected_close_date' => now()->addDays(45)->toDateString(),
        ]);

        $response->assertRedirect(route('sales.index', ['tab' => 'pipeline']));

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);
        $this->assertNotNull($lead->converted_at);

        $opp = SalesOpportunity::where('lead_id', $lead->id)->first();
        $this->assertNotNull($opp);
        $this->assertEquals('CRDB Cloud Infrastructure Overhaul', $opp->title);
        $this->assertEquals(15000000, $opp->estimated_value);
        $this->assertEquals('qualification', $opp->stage);

        // Check auto conversion activity log
        $act = SalesActivity::where('customer_name', 'Neema Shirima')->first();
        $this->assertNotNull($act);
        $this->assertEquals('completed', $act->status);
    }

    public function test_suitecrm_quote_duplication(): void
    {
        $this->actingAs($this->user);

        $quote = SalesQuotation::create([
            'customer_name' => 'Airtel Tech Team',
            'company' => 'Airtel Tanzania',
            'subtotal' => 1000000,
            'discount' => 0,
            'total_amount' => 1180000,
            'status' => 'sent',
            'created_by' => $this->user->id,
        ]);

        $quote->items()->create([
            'item_name' => 'Optical Fiber Patch Cords',
            'item_description' => 'Single-mode LC-LC 10m',
            'quantity' => 10,
            'unit_price' => 100000,
            'total_price' => 1000000,
        ]);

        $response = $this->post(route('sales.quotations.duplicate', $quote->id));
        $response->assertRedirect(route('sales.index', ['tab' => 'quotations']));

        $cloned = SalesQuotation::latest('id')->first();
        $this->assertNotNull($cloned);
        $this->assertNotEquals($quote->id, $cloned->id);
        $this->assertEquals('draft', $cloned->status);
        $this->assertCount(1, $cloned->items);
    }
}
