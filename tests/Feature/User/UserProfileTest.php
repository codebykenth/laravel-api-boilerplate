<?php

use App\Models\User;

test('authenticated user can fetch their profile', function () {
    $user = User::factory()->create([
        'name' => 'Alex Developer',
        'email' => 'alex@example.com',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/user');

    $response->assertOk()
        ->assertJson([
            'id' => $user->id,
            'name' => 'Alex Developer',
            'email' => 'alex@example.com',
        ]);
});

test('unauthenticated user cannot fetch profile', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});
