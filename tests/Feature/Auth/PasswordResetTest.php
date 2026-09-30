<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('forgot password sends reset link email and returns success message', function () {
    $user = User::factory()->create(['email' => 'resetme@example.com']);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'resetme@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => __('passwords.sent'),
        ]);
});

test('forgot password returns generic success for non-existent email to prevent user enumeration', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => __('passwords.sent'),
        ]);
});

test('forgot password validates required email', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('reset password view is accessible with token', function () {
    $response = $this->get('/api/v1/reset-password/sample-token?email=user@example.com');

    $response->assertOk()
        ->assertViewIs('auth.reset-password')
        ->assertViewHas('token', 'sample-token')
        ->assertViewHas('email', 'user@example.com');
});

test('user can reset password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'user@example.com',
        'password' => 'new-strong-password',
        'password_confirmation' => 'new-strong-password',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => __('passwords.reset'),
        ]);

    $this->assertTrue(Hash::check('new-strong-password', $user->fresh()->password));
});

test('reset password fails with invalid token', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => 'user@example.com',
        'password' => 'new-strong-password',
        'password_confirmation' => 'new-strong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
