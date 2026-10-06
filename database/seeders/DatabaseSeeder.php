<?php

namespace Database\Seeders;

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

        User::create([
            'role_id' => 2,
            'employee_id' => 1,
            'firstname' => 'Wiebe',
            'lastname' => 'Gouma',
            'email' => 'wiebe@gouma.nl',
            'password' => Hash::make('password'),
            'phonenumber' => 1234567890,

        ]);

        User::create([
            'role_id' => 1,
            'firstname' => 'Jan',
            'lastname' => 'Klant',
            'email' => 'jan@klant.nl',
            'password' => Hash::make('JanKlant'),
            'phonenumber' => 1234567890,
        ]);
    }
}
