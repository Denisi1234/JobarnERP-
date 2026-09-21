<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ItTicket;
use App\Models\User;
use App\Models\WorkReport;
use App\Services\ItService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_ticket_lifecycle_dispatches_real_notifications()
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        $itUser1 = User::factory()->create(['role' => 'it', 'name' => 'IT Tech 1']);
        $itUser2 = User::factory()->create(['role' => 'it', 'name' => 'IT Tech 2']);
        $salesUser = User::factory()->create(['role' => 'sales', 'name' => 'Sales Officer']);
        $receptionUser = User::factory()->create(['role' => 'reception', 'name' => 'Receptionist']);

        $service = app(ItService::class);

        // 1. Create ticket -> should notify IT users and Admin
        $this->actingAs($receptionUser);
        $ticketData = $service->createTicket([
            'visitor_name' => 'Juma Khamis',
            'title' => 'MacBook screen replacement',
            'category' => 'Hardware Repair',
            'priority' => 'high',
            'customer_type' => 'individual',
            'visitor_phone' => '0712345678',
        ]);

        $ticket = ItTicket::find($ticketData['id']);
        $this->assertNotNull($ticket);

        // IT users should have received notification
        $this->assertGreaterThan(0, $itUser1->unreadNotifications()->count());

        // 2. Reassign ticket -> should notify new assignee
        $this->actingAs($itUser1);
        $service->reassignTicket($ticket->id, $itUser2->id, 'Handing over to night shift technician', $itUser1->id);

        $this->assertGreaterThan(0, $itUser2->unreadNotifications()->count());
        $hasReassign = $itUser2->unreadNotifications()->get()->contains(fn($n)=> ($n->data['kind'] ?? '') === 'it_ticket_reassigned');
        $this->assertTrue($hasReassign, 'IT Tech 2 should have a reassign notification');
        $reassignNotif = $itUser2->unreadNotifications()->get()->first(fn($n)=> ($n->data['kind'] ?? '') === 'it_ticket_reassigned') ?? $itUser2->unreadNotifications()->latest('id')->first();

        // 3. Mark notification as read via controller — HARDENED POST only
        $this->actingAs($itUser2);
        $response = $this->post(route('notifications.read', $reassignNotif->id));
        $response->assertRedirect(route('it.tickets.show', $ticket->id));
        $this->assertNotNull($reassignNotif->fresh()->read_at);

        // 4. Resolve ticket -> should notify sales user for billing
        $service->resolveTicket($ticket->id, ['price' => 150000, 'work_completed' => 'Screen replaced', 'resolution_summary' => 'Tested and verified OK']);
        $this->assertGreaterThan(0, $salesUser->unreadNotifications()->count());
        $resolveNotif = $salesUser->unreadNotifications()->latest('id')->first();
        $this->assertEquals('it_ticket_resolved', $resolveNotif->data['kind']);
    }

    public function test_logbook_submission_dispatches_notification_to_manager()
    {
        $dept = Department::create(['name' => 'Information Technology', 'code' => 'IT', 'active' => true]);
        $manager = User::factory()->create(['role' => 'manager', 'name' => 'Manager User']);
        $itUser = User::factory()->create(['role' => 'it', 'name' => 'IT Staff']);

        $this->actingAs($itUser);
        $response = $this->post(route('work-reports.store'), [
            'department_id' => $dept->id,
            'report_date' => now()->toDateString(),
            'entry_time' => '08:00',
            'out_time' => '17:00',
            'activity_performed' => 'Serviced 3 laptops and diagnosed workstation motherboard.',
        ]);

        $response->assertSessionHas('success');
        $this->assertGreaterThan(0, $manager->unreadNotifications()->count());
        $notif = $manager->unreadNotifications()->first();
        $this->assertEquals('logbook_submitted', $notif->data['kind']);

        // Manager clicks to read — HARDENED POST only
        $this->actingAs($manager);
        $readResponse = $this->post(route('notifications.read', $notif->id));
        $readResponse->assertRedirect(route('manager.logbooks'));
    }
}
