<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkReport;
use Tests\TestCase;

class LogbookIsolationTest extends TestCase
{
    public function test_sales_logbook_isolation_and_manager_visibility()
    {
        $salesDept = Department::firstOrCreate(['name' => 'Sales & Business Dev']);
        $itDept = Department::firstOrCreate(['name' => 'Information Technology']);

        $salesUser = User::firstOrCreate(
            ['email' => 'sales_test@jobarn.co.tz'],
            ['name' => 'Sales Staff', 'password' => bcrypt('password'), 'role' => 'sales']
        );
        $itUser = User::firstOrCreate(
            ['email' => 'it_test@jobarn.co.tz'],
            ['name' => 'IT Staff', 'password' => bcrypt('password'), 'role' => 'it']
        );
        $managerUser = User::firstOrCreate(
            ['email' => 'manager_test@jobarn.co.tz'],
            ['name' => 'Executive Manager', 'password' => bcrypt('password'), 'role' => 'manager']
        );

        Employee::updateOrCreate(
            ['email' => $salesUser->email],
            ['first_name' => 'Sales', 'last_name' => 'Staff', 'department_id' => $salesDept->id]
        );
        Employee::updateOrCreate(
            ['email' => $itUser->email],
            ['first_name' => 'IT', 'last_name' => 'Staff', 'department_id' => $itDept->id]
        );

        // 1. Sales staff submits a logbook entry
        $this->actingAs($salesUser);
        $response = $this->post(route('work-reports.store'), [
            'report_date' => today()->format('Y-m-d'),
            'entry_time' => '08:00',
            'out_time' => '17:00',
            'activity_performed' => 'Closed 3 enterprise client proposals in Sales portal.',
        ]);
        $response->assertSessionHas('success');

        $salesReport = WorkReport::where('user_id', $salesUser->id)->first();
        $this->assertNotNull($salesReport);
        $this->assertEquals('Sales & Business Dev', $salesReport->department->name);

        // 2. Sales staff can see their own logbook in Sales Portal
        $this->get(route('sales.logbook'))
            ->assertOk()
            ->assertSee('Closed 3 enterprise client proposals');

        // 3. Sales staff visiting IT or Reception logbook sees empty (portal isolation, no redirect per hardened WorkReportController)
        $resp = $this->get(route('it.logbook'));
        if($resp->status()===500) dump(substr($resp->getContent(),0,2000));
        $resp->assertOk()->assertDontSee('Closed 3 enterprise client proposals');
        $resp2 = $this->get(route('reception.logbook'));
        if($resp2->status()===500) dump(substr($resp2->getContent(),0,2000));
        $resp2->assertOk()->assertDontSee('Closed 3 enterprise client proposals');

        // 4. IT staff viewing IT logbook CANNOT see the sales logbook entry
        $this->actingAs($itUser);
        $this->get(route('it.logbook'))
            ->assertOk()
            ->assertDontSee('Closed 3 enterprise client proposals');

        // 5. IT staff visiting Sales logbook sees empty (portal isolation)
        $this->get(route('sales.logbook'))
            ->assertOk()
            ->assertDontSee('Closed 3 enterprise client proposals');

        // 6. Manager CAN view all department logbooks in Manager portal
        $this->actingAs($managerUser);
        $this->get(route('manager.logbooks'))
            ->assertOk()
            ->assertSee('Closed 3 enterprise client proposals')
            ->assertSee('Sales & Business Dev');
    }
}
