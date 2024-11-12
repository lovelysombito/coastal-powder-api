<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginTest extends TestCase
{

    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_user_cannot_login_with_invalid_password()
    {
        $user = User::factory()->make(['scope'=>'administrator']);

        $response = $this->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('api/auth/login', [
            'email' => $user->email,
            'password' => 'invalid-password',
        ]);

        $response
            // ->assertStatus(401)
            ->assertOk()
            ->assertExactJson([
                "message" => "The provided credentials are invalid"
            ]);
    }

    public function test_user_cannot_login_with_invalid_email()
    {
        $response = $this->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('api/auth/login', [
            'email' => 'unknownemail@gmail.com',
            'password' => 'invalid-password',
        ]);

        $response
            // ->assertStatus(401)
            ->assertOk()
            ->assertExactJson([
                "message" => "The provided credentials are invalid"
            ]);
    }

    public function test_user_cannot_login_with_unverified_email()
    {
        $user = User::factory()->create(['scope'=>'administrator']);

        $response = $this->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            // ->assertStatus(401)
            ->assertOk()
            ->assertExactJson([
                "message" => "The provided credentials are invalid"
            ]);
    }

    public function test_user_can_login_with_correct_credentials()
    {

        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                "message" => "Success",
                "data" => [
                    "email" => $user->email,
                    "user_id" => $user->user_id,
                    "firstname" => $user->firstname,
                    "lastname" => $user->lastname,
                    "scope" => $user->scope,
                ]
            ]);
    }

    public function test_user_can_logout()
    {

        $user = User::factory()->create(['scope'=>'administrator']);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertExactJson([
                "message" => "Success"
            ]);
    }
}
