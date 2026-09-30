<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

test('authenticated user can enable two-factor authentication', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/enable');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Two-factor authentication enabled. Please confirm your setup.',
        ])
        ->assertJsonStructure([
            'data' => ['svg', 'secret_key', 'recovery_codes'],
        ]);

    $this->assertNotNull($user->fresh()->two_factor_secret);
    $this->assertNull($user->fresh()->two_factor_confirmed_at);
});

test('user can confirm two-factor authentication with valid code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1', 'code-2'])),
    ]);

    $mock = Mockery::mock(TwoFactorAuthenticationProvider::class);
    $mock->shouldReceive('verify')->andReturn(true);
    app()->instance(TwoFactorAuthenticationProvider::class, $mock);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/confirm', [
            'code' => '123456',
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Two-factor authentication confirmed successfully',
        ]);

    $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
});

test('user can retrieve two-factor recovery codes', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-alpha', 'code-beta'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/two-factor/recovery-codes');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => [
                'recovery_codes' => ['code-alpha', 'code-beta'],
            ],
        ]);
});

test('user can regenerate two-factor recovery codes', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['old-code-1', 'old-code-2'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/auth/two-factor/recovery-codes');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => ['recovery_codes'],
        ]);

    $freshCodes = $user->fresh()->recoveryCodes();
    $this->assertNotEquals(['old-code-1', 'old-code-2'], $freshCodes);
});

test('user can disable two-factor authentication', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/auth/two-factor/disable');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Two-factor authentication disabled successfully',
        ]);

    $this->assertNull($user->fresh()->two_factor_secret);
    $this->assertNull($user->fresh()->two_factor_recovery_codes);
    $this->assertNull($user->fresh()->two_factor_confirmed_at);
});

test('user can complete two-factor challenge using recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['my-valid-recovery-code'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $challengeToken = Str::random(64);
    cache()->put('2fa_challenge:'.$challengeToken, [
        'user_id' => $user->id,
        'remember' => false,
        'token_name' => 'auth_token',
    ], now()->addMinutes(5));

    $response = $this->postJson('/api/v1/auth/two-factor-challenge', [
        'challenge_token' => $challengeToken,
        'recovery_code' => 'my-valid-recovery-code',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => ['id', 'email'],
                'token',
            ],
        ]);
});

test('two-factor challenge fails with invalid recovery code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['valid-code'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $challengeToken = Str::random(64);
    cache()->put('2fa_challenge:'.$challengeToken, [
        'user_id' => $user->id,
        'remember' => false,
        'token_name' => 'auth_token',
    ], now()->addMinutes(5));

    $response = $this->postJson('/api/v1/auth/two-factor-challenge', [
        'challenge_token' => $challengeToken,
        'recovery_code' => 'wrong-recovery-code',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['recovery_code']);
});
