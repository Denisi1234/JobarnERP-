<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PortalUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Aisha Mwinyi — Reception', 'email' => 'reception@jobarn.co.tz', 'role' => 'reception'],
            ['name' => 'Rajabu Simba — IT Support', 'email' => 'it@jobarn.co.tz', 'role' => 'it'],
            ['name' => 'Neema Moshi — Sales', 'email' => 'sales@jobarn.co.tz', 'role' => 'sales'],
            ['name' => 'Hassan Mtamba — Manager', 'email' => 'manager@jobarn.co.tz', 'role' => 'manager'],
            ['name' => 'Jobarn Admin', 'email' => 'admin@jobarn.co.tz', 'role' => 'admin', 'is_admin' => true],
            ['name' => 'Legacy Admin', 'email' => 'admin@frontdesk.test', 'role' => 'admin', 'is_admin' => true],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('12345678'),
                    'role' => $u['role'],
                    'is_admin' => $u['is_admin'] ?? false,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
