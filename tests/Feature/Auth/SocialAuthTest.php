<?php

use App\Models\User;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('redirect to provider generates redirect url for supported provider', function () {
    $response = $this->getJson('/api/v1/auth/google');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Redirect URL generated successfully',
        ])
        ->assertJsonStructure([
            'data' => ['url'],
        ]);

    $this->assertStringContainsString('accounts.google.com', $response->json('data.url'));
});

test('unsupported social provider returns not found', function () {
    $response = $this->getJson('/api/v1/auth/github');

    $response->assertNotFound();
});

test('social callback authenticates new user', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-unique-id-1234');
    $socialiteUser->shouldReceive('getName')->andReturn('Google User');
    $socialiteUser->shouldReceive('getEmail')->andReturn('social@example.com');
    $socialiteUser->shouldReceive('getNickname')->andReturn(null);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->getJson('/api/v1/auth/google/callback');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'User logged in successfully',
        ])
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'email', 'name'],
                'token',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'social@example.com',
        'provider_id' => 'google-unique-id-1234',
        'provider' => 'google',
    ]);
});

test('social callback links to existing user by email', function () {
    $existing = User::factory()->create(['email' => 'existing@example.com']);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-id-999');
    $socialiteUser->shouldReceive('getName')->andReturn($existing->name);
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $socialiteUser->shouldReceive('getNickname')->andReturn(null);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->getJson('/api/v1/auth/google/callback');

    $response->assertOk();
    $this->assertEquals('google-id-999', $existing->fresh()->provider_id);
    $this->assertEquals('google', $existing->fresh()->provider->value);
});
