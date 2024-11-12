<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UsersTest extends TestCase
{

    use RefreshDatabase;

    public function test_administrator_can_invite_user()
    {

        Notification::fake();

        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/users', [
            "firstname" => "firstname",
            "lastname" => "lastname", 
            "email" => "test@gmail.com",
            "scope" => "administrator"
        ]);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                "message" => "User has successfully been invited"
            ]);

        $newUser = User::where('email',"test@gmail.com")->first();

        $token = $newUser->confirmation_token;

        Notification::assertSentTo($newUser, VerifyEmail::class, function ($notification, $channels) use ($token) {
            return $notification->getToken() === $token;
        });
    }

    public function test_non_administrator_cannot_invite_user()
    {
        $user = User::factory()->create(['scope'=>'supervisor']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/users', [
            "firstname" => "firstname",
            "lastname" => "lastname", 
            "email" => "test@gmail.com",
            "scope" => "supervisor"
        ]);

        $response
            ->assertStatus(403)
            ->assertExactJson([
                "message" => "You don't have permission to access this resource"
            ]);
    }

    public function test_invite_fails_with_invalid_scope()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/users', [
            "firstname" => "firstname",
            "lastname" => "lastname", 
            "email" => "test@gmail.com",
            "scope" => "invalid_scope"
        ]);

        $response
            ->assertStatus(422)
            ->assertExactJson([
                "message" => "The selected scope is invalid.",
                "errors" => [
                    "scope" => [
                        "The selected scope is invalid."
                    ]
                ]
            ]);
    }

    public function test_invite_fails_with_existing_email()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/users', [
            "firstname" => "firstname",
            "lastname" => "lastname", 
            "email" => $user->email,
            "scope" => "administrator"
        ]);

        $response
            ->assertStatus(400)
            ->assertExactJson([
                "message" => "A user with this email address already exists",
            ]);
    }

    public function test_administrator_can_resend_email_verification()
    {

        Notification::fake();

        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $newUser = User::factory()->create(['scope'=>'administrator']);
        $payload = [
            'iss' => env('APP_URL'),
            'aud' => env('APP_URL'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time()+1800,
            'user_id' => $newUser->user_id,
        ];

        do {
            $token = JWT::encode($payload, env('APP_KEY'), 'HS256');
        } while (!User::where('confirmation_token', $token));

        $newUser->forceFill(['confirmation_token' => $token])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->patchJson('/api/users/'.$newUser->user_id);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                "message" => "The verification email has successfully been resent"
            ]);

        $token = $newUser->confirmation_token;

        Notification::assertSentTo($newUser, VerifyEmail::class, function ($notification, $channels) use ($token) {
            return $notification->getToken() === $token;
        });
    }

    public function test_administrator_cannot_send_email_for_already_verified_user()
    {

        Notification::fake();

        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->patchJson('/api/users/'.$user->user_id);

        $response
            ->assertStatus(400)
            ->assertExactJson([
                "message" => "The user has already verified their email address"
            ]);

        Notification::assertNothingSent();
    }

    public function test_administrator_cannot_send_email_for_user_that_does_not_exist()
    {

        Notification::fake();

        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->patchJson('/api/users/invalid');

        $response
            ->assertStatus(404)
            ->assertExactJson([
                "message" => "The requested user does not exist"
            ]);

        Notification::assertNothingSent();
    }
}
