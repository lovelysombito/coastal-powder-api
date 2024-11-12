<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // run only on local environment
        if (App::Environment() === 'local') {
            $this->call([
                // UserSeeder::class,
                // ProductSeeder::class,
                // DealSeeder::class,
                // JobScheduleSeeder::class,
                // QcSeeder::class,
                // DispatchSeeder::class,
                // LineItemSeeder::class,
                // CommentSeeder::class
            ]);
        }

        if (App::Environment() === 'testing') {
            $this->call([
                ProductSeeder::class,
            ]);
        }

        $this->call([
            IntegrationSeeder::class,
            ProductionUserSeeder::class,
            ColorSeeder::class,
            TreatmentSeeder::class,
            MaterialSeeder::class,
            MaterialTreatmentSeeder::class            
        ]);
    }
}
