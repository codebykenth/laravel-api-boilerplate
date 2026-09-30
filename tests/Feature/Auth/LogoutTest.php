<?php

use App\Models\User;

test('authenticated user can logout and revoke token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'User logged out successfully',
        ]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('unauthenticated user cannot logout', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertUnauthorized();
});
