<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         if (!User::where('email', 'support@upstreamtech.io')->exists()) {
            User::create([
                'firstname' => 'upstream',
                'lastname' => 'support',
                'email' => 'support@upstreamtech.io',
                'password' => Hash::make('password'),
                'email_verified_at' => Carbon::now(),
                'scope' => 'administrator',
            ]);
        }
    }
}
