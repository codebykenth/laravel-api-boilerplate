<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can login with valid credentials and receive token', function () {
    $user = User::factory()->create([
        'email' => 'testuser@example.com',
        'password' => Hash::make('secret1234'),
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'testuser@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'User logged in successfully',
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
                'token',
            ],
        ]);
});

test('login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'testuser@example.com',
        'password' => Hash::make('secret1234'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'testuser@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

test('login requires verified email', function () {
    User::factory()->unverified()->create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('secret1234'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'unverified@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', 'Please verify your email first.');
});

test('login validates required email and password fields', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

test('login triggers two-factor challenge when 2fa is confirmed', function () {
    $user = User::factory()->create([
        'email' => '2fa@example.com',
        'password' => Hash::make('secret1234'),
        'email_verified_at' => now(),
        'two_factor_secret' => encrypt('secret-key'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => '2fa@example.com',
        'password' => 'secret1234',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.two_factor', true)
        ->assertJsonStructure([
            'data' => [
                'two_factor',
                'challenge_token',
            ],
        ]);
});
