<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        //        User::factory()->create([
        //            'firstname' => 'Test',
        //            'lastname' => 'User',
        //            'email' => 'test@example.com',
        //        ]);

        $Dentist = User::create([
            'role_id' => 2,
            'firstname' => 'Wiebe',
            'lastname' => 'Gouma',
            'email' => 'wiebe@gouma.nl',
            'password' => Hash::make('password'),
            'phonenumber' => 1234567890,
        ]);
        $Dentist->update([
            'employee_id' => 1,
        ]);

        User::create([
            'role_id' => 1,
            'firstname' => 'Jan',
            'lastname' => 'Klant',
            'email' => 'jan@klant.nl',
            'password' => Hash::make('JanKlant'),
            'phonenumber' => 1234567890,
        ]);

        User::create([
            'role_id' => Role::idFor(Role::ADMIN),
            'firstname' => 'Admin',
            'lastname' => 'Beheerder',
            'email' => 'admin@tandarts.nl',
            'password' => Hash::make('password'),
        ]);

        // Demo customer with a few upcoming and past appointments, so the customer start screen has something to show
        $customer = User::factory()->create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'phonenumber' => '06 12345678',
            'employee_id' => $Dentist->id,
        ]);

        $treatments = Treatment::factory(4)->create();

        Appointment::factory(3)->for($customer, 'customer')->for($Dentist, 'dentist')->create()
            ->each(fn (Appointment $appointment) => $appointment->treatments()->attach($treatments->random()));

        Appointment::factory(3)->completed()->for($customer, 'customer')->for($Dentist, 'dentist')->create()
            ->each(fn (Appointment $appointment) => $appointment->treatments()->attach($treatments->random()));
    }
}
