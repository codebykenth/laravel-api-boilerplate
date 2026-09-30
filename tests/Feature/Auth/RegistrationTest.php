<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

test('user can register with valid credentials', function () {
    Event::fake([Registered::class]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Developer',
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'User registered successfully',
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email'],
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'name' => 'Jane Developer',
    ]);

    Event::assertDispatched(Registered::class);
});

test('registration requires name, email, and password', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration fails when email is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Another User',
        'email' => 'taken@example.com',
        'password' => 'password123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('registration fails when password is less than 8 characters', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Short Pass',
        'email' => 'short@example.com',
        'password' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
