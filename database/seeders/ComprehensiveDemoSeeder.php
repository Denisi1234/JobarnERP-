<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\ItTicket;
use App\Models\SaleInvoice;
use App\Models\Task;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComprehensiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Departments exist
        $adminDept = Department::firstOrCreate(['name' => 'Administration'], ['description' => 'Executive & Office Admin']);
        $itDept = Department::firstOrCreate(['name' => 'Information Technology'], ['description' => 'ICT, Infrastructure & Hardware Support']);
        $salesDept = Department::firstOrCreate(['name' => 'Sales & Business Dev'], ['description' => 'Sales, Invoicing & Client Relations']);
        $opsDept = Department::firstOrCreate(['name' => 'Front Desk Operations'], ['description' => 'Lobby, Visitor Management & Security']);
        $hrDept = Department::firstOrCreate(['name' => 'Human Resources'], ['description' => 'People Operations & Talent']);

        // 2. Ensure Designations exist
        $leadDesig = Designation::firstOrCreate(['name' => 'Department Lead']);
        $officerDesig = Designation::firstOrCreate(['name' => 'Officer']);
        $specialistDesig = Designation::firstOrCreate(['name' => 'Technical Specialist']);
        $executiveDesig = Designation::firstOrCreate(['name' => 'Executive Director']);

        // 3. Seed Realistic Staff / Employees
        $staffMembers = [
            ['first_name' => 'Aisha', 'last_name' => 'Mwinyi', 'email' => 'aisha.mwinyi@jobarn.co.tz', 'dept' => $opsDept, 'desig' => $leadDesig],
            ['first_name' => 'Rajabu', 'last_name' => 'Simba', 'email' => 'rajabu.simba@jobarn.co.tz', 'dept' => $itDept, 'desig' => $specialistDesig],
            ['first_name' => 'Neema', 'last_name' => 'Moshi', 'email' => 'neema.moshi@jobarn.co.tz', 'dept' => $salesDept, 'desig' => $officerDesig],
            ['first_name' => 'Hassan', 'last_name' => 'Mtamba', 'email' => 'hassan.mtamba@jobarn.co.tz', 'dept' => $adminDept, 'desig' => $executiveDesig],
            ['first_name' => 'Baraka', 'last_name' => 'Mwita', 'email' => 'baraka.mwita@jobarn.co.tz', 'dept' => $itDept, 'desig' => $officerDesig],
            ['first_name' => 'Grace', 'last_name' => 'Makani', 'email' => 'grace.makani@jobarn.co.tz', 'dept' => $hrDept, 'desig' => $leadDesig],
            ['first_name' => 'David', 'last_name' => 'Kilonzo', 'email' => 'david.kilonzo@jobarn.co.tz', 'dept' => $salesDept, 'desig' => $specialistDesig],
        ];

        $employees = [];
        foreach ($staffMembers as $s) {
            $employees[] = Employee::firstOrCreate(
                ['email' => $s['email']],
                [
                    'first_name' => $s['first_name'],
                    'last_name' => $s['last_name'],
                    'department_id' => $s['dept']->id,
                    'designation_id' => $s['desig']->id,
                ]
            );
        }

        // 4. Seed Live Visits (Active in lobby & departed)
        $sampleVisits = [
            [
                'visitor' => 'Juma Khamis Mwinyi',
                'visitor_phone' => '+255 714 552 110',
                'visitor_email' => 'juma.m@tangacement.com',
                'purpose' => 'PC Maintenance & Hardware Diagnosis',
                'arrival' => now()->subMinutes(45),
                'departure' => null,
                'employee_id' => $employees[1]->id, // Rajabu (IT)
            ],
            [
                'visitor' => 'Fatma Said Rashid',
                'visitor_phone' => '+255 754 883 992',
                'visitor_email' => 'fatma@crdbbank.co.tz',
                'purpose' => 'Corporate IT Equipment Supplier Review',
                'arrival' => now()->subMinutes(80),
                'departure' => null,
                'employee_id' => $employees[2]->id, // Neema (Sales)
            ],
            [
                'visitor' => 'Kelvin Mbise',
                'visitor_phone' => '+255 682 120 443',
                'visitor_email' => 'kelvin.mbise@vodacom.co.tz',
                'purpose' => 'Fiber & Network Maintenance Check',
                'arrival' => now()->subHours(3),
                'departure' => now()->subMinutes(30),
                'employee_id' => $employees[1]->id, // Rajabu (IT)
            ],
            [
                'visitor' => 'Zawadi Ally',
                'visitor_phone' => '+255 788 334 112',
                'visitor_email' => 'zawadi@precisionair.co.tz',
                'purpose' => 'Executive Meeting with Operations',
                'arrival' => now()->subHours(4),
                'departure' => now()->subHours(2),
                'employee_id' => $employees[3]->id, // Hassan (Manager)
            ],
        ];

        foreach ($sampleVisits as $v) {
            Visit::firstOrCreate(
                ['visitor' => $v['visitor'], 'visitor_phone' => $v['visitor_phone']],
                [
                    'uuid' => (string) Str::uuid(),
                    'visitor_email' => $v['visitor_email'],
                    'purpose' => $v['purpose'],
                    'arrival' => $v['arrival'],
                    'departure' => $v['departure'],
                    'employee_id' => $v['employee_id'],
                ]
            );
        }

        // 5. Seed IT Tickets & Connected Tasks & Sales Invoices
        $rajabuUser = User::where('role', 'it')->first();
        $receptionUser = User::where('role', 'reception')->first();

        // Ticket 1: Resolved & Invoiced (Paid)
        $t1 = ItTicket::firstOrCreate(
            ['title' => 'HP ZBook Laptop Overheating & Battery Replacement'],
            [
                'uuid' => (string) Str::uuid(),
                'visitor_name' => 'Kelvin Mbise',
                'visitor_company' => 'Vodacom Tanzania',
                'visitor_phone' => '+255 682 120 443',
                'description' => 'Clean thermal paste, fan servicing, and replacement battery installation.',
                'category' => 'PC Maintenance',
                'priority' => 'high',
                'status' => 'resolved',
                'price' => 120000,
                'location' => 'ICT Lab — Workstation 2',
                'reported_by' => $receptionUser?->id,
                'assigned_to' => $rajabuUser?->id,
            ]
        );

        $task1 = Task::firstOrCreate(
            ['it_ticket_id' => $t1->id],
            [
                'uuid' => (string) Str::uuid(),
                'title' => 'Service HP ZBook — Kelvin Mbise',
                'description' => 'Hardware thermal cleaning & battery diagnostic',
                'status' => 'completed',
                'assigned_to' => $rajabuUser?->id,
                'completed_at' => now()->subMinutes(30),
            ]
        );
        $t1->update(['task_id' => $task1->id]);

        SaleInvoice::firstOrCreate(
            ['it_ticket_id' => $t1->id],
            [
                'uuid' => (string) Str::uuid(),
                'customer_name' => 'Kelvin Mbise',
                'company' => 'Vodacom Tanzania',
                'service' => 'HP ZBook Laptop Overheating & Battery Replacement',
                'amount' => 120000,
                'status' => 'paid',
                'paid_at' => now()->subMinutes(15),
            ]
        );

        // Ticket 2: Resolved & Invoiced (Pending Payment)
        $t2 = ItTicket::firstOrCreate(
            ['title' => 'Dell OptiPlex Desktop PSU Diagnostic & Windows Re-installation'],
            [
                'uuid' => (string) Str::uuid(),
                'visitor_name' => 'Juma Khamis Mwinyi',
                'visitor_company' => 'Tanga Cement Ltd',
                'visitor_phone' => '+255 714 552 110',
                'description' => 'Desktop will not boot past POST. Repaired capacitor & fresh OS installation.',
                'category' => 'PC Maintenance',
                'priority' => 'urgent',
                'status' => 'resolved',
                'price' => 85000,
                'location' => 'Lobby 1 — Dar HQ',
                'reported_by' => $receptionUser?->id,
                'assigned_to' => $rajabuUser?->id,
            ]
        );

        $task2 = Task::firstOrCreate(
            ['it_ticket_id' => $t2->id],
            [
                'uuid' => (string) Str::uuid(),
                'title' => 'Repair OptiPlex PSU & OS — Juma Mwinyi',
                'description' => 'Hardware power supply fix and software recovery',
                'status' => 'completed',
                'assigned_to' => $rajabuUser?->id,
                'completed_at' => now()->subMinutes(10),
            ]
        );
        $t2->update(['task_id' => $task2->id]);

        SaleInvoice::firstOrCreate(
            ['it_ticket_id' => $t2->id],
            [
                'uuid' => (string) Str::uuid(),
                'customer_name' => 'Juma Khamis Mwinyi',
                'company' => 'Tanga Cement Ltd',
                'service' => 'Dell OptiPlex Desktop PSU Diagnostic & Windows Re-installation',
                'amount' => 85000,
                'status' => 'pending_payment',
                'paid_at' => null,
            ]
        );

        // Ticket 3: Assigned & In Progress
        $t3 = ItTicket::firstOrCreate(
            ['title' => 'Lenovo ThinkPad Screen Backlight Repair'],
            [
                'uuid' => (string) Str::uuid(),
                'visitor_name' => 'Fatma Said Rashid',
                'visitor_company' => 'CRDB Bank',
                'visitor_phone' => '+255 754 883 992',
                'description' => 'Display flickers when opening hinge. Display cable ribbon replacement needed.',
                'category' => 'PC Maintenance',
                'priority' => 'medium',
                'status' => 'assigned',
                'price' => 60000,
                'location' => 'ICT Workbench 1',
                'reported_by' => $receptionUser?->id,
                'assigned_to' => $rajabuUser?->id,
            ]
        );

        $task3 = Task::firstOrCreate(
            ['it_ticket_id' => $t3->id],
            [
                'uuid' => (string) Str::uuid(),
                'title' => 'Screen Ribbon Replacement — Fatma Rashid',
                'description' => 'Inspect EDP ribbon cable and re-seat connector',
                'status' => 'pending',
                'assigned_to' => $rajabuUser?->id,
            ]
        );
        $t3->update(['task_id' => $task3->id]);
    }
}
