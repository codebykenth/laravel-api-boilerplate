<?php

use App\Models\User;

test('passkey public login options can be generated', function () {
    $response = $this->getJson('/api/v1/auth/passkeys/options');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Passkey login options generated successfully',
        ])
        ->assertJsonStructure([
            'data' => [
                'options' => ['challenge'],
                'challenge_token',
            ],
        ]);
});

test('passkey registration options require authentication', function () {
    $response = $this->getJson('/api/v1/passkeys/options');

    $response->assertUnauthorized();
});

test('authenticated user can generate passkey registration options', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/passkeys/options');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Passkey registration options generated successfully',
        ])
        ->assertJsonStructure([
            'data' => [
                'options' => ['challenge', 'user'],
                'challenge_token',
            ],
        ]);
});

test('authenticated user can list their passkeys', function () {
    $user = User::factory()->create();

    $passkey = $user->passkeys()->create([
        'name' => 'My iPhone',
        'credential_id' => 'cred-iphone-001',
        'credential' => ['type' => 'public-key'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/passkeys');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Passkeys retrieved successfully',
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'My iPhone');
});

test('authenticated user can delete their passkey', function () {
    $user = User::factory()->create();

    $passkey = $user->passkeys()->create([
        'name' => 'My YubiKey',
        'credential_id' => 'cred-yubikey-001',
        'credential' => ['type' => 'public-key'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/passkeys/{$passkey->id}");

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Passkey deleted successfully',
        ]);

    $this->assertDatabaseMissing('passkeys', [
        'id' => $passkey->id,
    ]);
});

test('user cannot delete another users passkey', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $passkey = $owner->passkeys()->create([
        'name' => 'Owner Key',
        'credential_id' => 'cred-owner-001',
        'credential' => ['type' => 'public-key'],
    ]);

    $response = $this->actingAs($attacker, 'sanctum')
        ->deleteJson("/api/v1/passkeys/{$passkey->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('passkeys', [
        'id' => $passkey->id,
    ]);
});
