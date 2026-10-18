<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $dentist = User::factory()->dentist()->create([
            'firstname' => 'Sanne',
            'lastname' => 'de Vries',
            'email' => 'tandarts@example.com',
        ]);

        $customer = User::factory()->create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'phonenumber' => '06 12345678',
            'employee_id' => $dentist->id,
        ]);

        $treatments = Treatment::factory(4)->create();

        // A few upcoming and past appointments so the customer start screen has something to show.
        Appointment::factory(3)->for($customer, 'customer')->for($dentist, 'dentist')->create()
            ->each(fn (Appointment $appointment) => $appointment->treatments()->attach($treatments->random()));

        Appointment::factory(3)->completed()->for($customer, 'customer')->for($dentist, 'dentist')->create()
            ->each(fn (Appointment $appointment) => $appointment->treatments()->attach($treatments->random()));
    }
}
