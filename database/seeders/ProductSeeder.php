<?php

namespace Database\Seeders;

use App\Models\Products;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i <= 5; $i++) {
            Products::create([
                'product_id' => 'product' . $i,
                'product_name' => 'Product' . $i,
                'description' => Str::random(10),
                'price' => rand(10, 100),
            ]);
        }
        for ($i = 6; $i <= 10; $i++) {
            Products::create([
                'product_id' => 'product' . $i,
                'product_name' => 'Product' . $i,
                'description' => Str::random(10),
                'price' => rand(10, 100),
                'brand' => 'Brand' . $i,
                'file_link' => 'file_link',
            ]);
        }
    }
}
