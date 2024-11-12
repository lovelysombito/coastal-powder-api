<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{

    use RefreshDatabase;

    public function test_user_requires_confirmed_password_to_enable_2fa()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/two-factor-authentication', []);

        $response
            ->assertStatus(423)
            ->assertExactJson([
                "message" => "Password confirmation required."
            ]);
    }


    public function test_user_can_enable_2fa()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/confirm-password', [
            'password' => 'password'
        ]);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/two-factor-authentication', []);

        $response->assertStatus(200);
    }

    public function test_user_can_disable_2fa()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/confirm-password', [
            'password' => 'password'
        ]);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->deleteJson('/api/auth/two-factor-authentication', []);

        $response->assertStatus(200);
    }

    public function test_user_can_retrieve_qr_code()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/confirm-password', [
            'password' => 'password'
        ]);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/two-factor-authentication', []);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->getJson('/api/auth/two-factor-qr-code', []);

        $response->assertStatus(200);
        $this->assertNotNull($response['url']);
        $this->assertNotNull($response['svg']);
    }

    public function test_user_cannot_retrieve_qr_code_if_2fa_disabled()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/confirm-password', [
            'password' => 'password'
        ]);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->getJson('/api/auth/two-factor-qr-code', []);

        $response
            ->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_user_can_retrieve_2fa_recovery_codes()
    {
        $user = User::factory()->create(['scope'=>'administrator']);
        $user->forceFill(['email_verified_at' => $user->freshTimestamp(), 'confirmation_token' => null])->save();

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/confirm-password', [
            'password' => 'password'
        ]);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->postJson('/api/auth/two-factor-authentication', []);

        $response = $this->actingAs($user)->withHeaders([
            'referer' => env('SPA_URL'),
            'origin' => env('SPA_URL'),
        ])->getJson('/api/auth/two-factor-recovery-codes', []);

        $response
            ->assertStatus(200)
            ->assertExactJson($user->recoveryCodes());
    }
}
