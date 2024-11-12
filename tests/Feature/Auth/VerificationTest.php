<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VerificationTest extends TestCase
{

    use RefreshDatabase;

    public function test_user_can_verify_email()
    {

        $user = User::factory()->create(['scope'=>'administrator']);
        $payload = [
            'iss' => env('APP_URL'),
            'aud' => env('APP_URL'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time()+1800,
            'user_id' => $user->user_id,
        ];

        do {
            $token = JWT::encode($payload, env('APP_KEY'), 'HS256');
        } while (!User::where('confirmation_token', $token));

        $user->forceFill(['confirmation_token' => $token])->save();

        $response = $this->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/verify', [
            "token" => $token,
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response
            ->assertOk()
            ->assertExactJson([
                "message" => "Email has successfully been verified"
            ]);
    }

    public function test_email_verification_fails_with_mismatched_passwords()
    {

        $user = User::factory()->create(['scope'=>'administrator']);
        $payload = [
            'iss' => env('APP_URL'),
            'aud' => env('APP_URL'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time()+1800,
            'user_id' => $user->user_id,
        ];

        do {
            $token = JWT::encode($payload, env('APP_KEY'), 'HS256');
        } while (!User::where('confirmation_token', $token));

        $user->forceFill(['confirmation_token' => $token])->save();

        $response = $this->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/verify', [
            "token" => $token,
            'password' => 'password',
            'password_confirmation' => 'wrong_password'
        ]);

        $response
            ->assertStatus(422)
            ->assertExactJson([
                "message" => "The password confirmation does not match.",
                "errors" => [
                    "password" => [
                        "The password confirmation does not match."
                    ]
                ]
            ]);
    }

    public function test_email_verification_fails_with_invalid_token()
    {

        $user = User::factory()->create(['scope'=>'administrator']);
        $payload = [
            'iss' => env('APP_URL'),
            'aud' => env('APP_URL'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time()+1800,
            'user_id' => $user->user_id,
        ];

        do {
            $token = JWT::encode($payload, env('APP_KEY'), 'HS256');
        } while (!User::where('confirmation_token', $token));

        $user->forceFill(['confirmation_token' => $token])->save();

        $response = $this->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/verify', [
            "token" => "invalid_token",
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response
            ->assertStatus(401)
            ->assertExactJson([
                "message" => "Please provide a valid verification token"
            ]);
    }

    public function test_user_can_confirm_password_for_higher_actions()
    {

        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/confirm-password', [
            'password' => 'password'
        ]);

        $response->assertStatus(201);
    }

    public function test_confirm_password_fails_with_invalid_password()
    {

        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/confirm-password', [
            'password' => 'wrong_password'
        ]);

        $response
            ->assertStatus(422)
            ->assertExactJson([
                "message" => "The provided password was incorrect.",
                "errors" => [
                    "password" => [
                        "The provided password was incorrect."
                    ]
                ]
            ]);
    }
}
