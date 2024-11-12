<?php

namespace Tests\Feature\Settings;

use App\Models\Products;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ProductsTest extends TestCase
{

    use RefreshDatabase;
    
    public function test_administrator_can_add_a_product() {

        $this->seed();
        $user = User::where('email', 'support@upstreamtech.io')->first();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/products', [
            "product_name" => "product",
            "description" => "description",
            "price" => 50.0
        ]);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                "message" => "Product successfully added"
            ]);
    }

    public function test_administrator_can_add_a_product_with_optional_fields() {

        $this->seed();
        $user = User::where('email', 'support@upstreamtech.io')->first();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/products', [
            "product_name" => "product",
            "description" => "description",
            "price" => 50.0,
            "file_link" => "https://www.google.com",
            "brand" => "Product Brand"
        ]);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                "message" => "Product successfully added"
            ]);
    }

    public function test_administrator_can_update_a_product() {

        $this->seed();
        $user = User::where('email', 'support@upstreamtech.io')->first();

        $product = Products::first();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->putJson('/api/products/'.$product->product_id, [
            "product_name" => "product",
            "description" => "description",
            "price" => 50,
        ]);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                "message" => "Product successfully updated"
            ]);
    }

    public function test_administrator_can_update_a_product_with_optional_fields() {

        $this->seed();
        $user = User::where('email', 'support@upstreamtech.io')->first();

        $product = Products::first();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->putJson('/api/products/'.$product->product_id, [
            "product_name" => "product",
            "description" => "description",
            "price" => 50.0,
            "file_link" => "https://www.google.com",
            "brand" => "Product Brand"
        ]);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                "message" => "Product successfully updated"
            ]);
    }

    public function test_user_can_get_all_products() {

        $this->seed();
        $user = User::where('email', 'support@upstreamtech.io')->first();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->getJson('/api/products', [
            "product_name" => "product",
            "description" => "description",
            "price" => 50.0,
            "file_link" => "https://www.google.com",
            "brand" => "Product Brand"
        ]);
        
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'product_name',
                            'description',
                            'price',
                            'file_link',
                            'brand',
                            'updated_at',
                        ]
                    ]
                ],
               
            ])
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data.data', 10)
                        ->etc()
            );
    }

    public function test_user_can_delete_product() {
        $this->seed();
        $user = User::where('email', 'support@upstreamtech.io')->first();

        $product = Products::first();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->deleteJson('/api/products/'.$product->product_id);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                "message" => "Product successfully deleted"
            ]);
    }
}
