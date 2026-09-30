<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification notice view can be rendered', function () {
    $response = $this->get('/api/v1/email/verify');

    $response->assertOk()
        ->assertViewIs('auth.verify-email')
        ->assertViewHas('status', 'notice');
});

test('email can be verified with valid signed url', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->get($verificationUrl);

    $response->assertOk()
        ->assertViewIs('auth.verify-email')
        ->assertViewHas('status', 'verified');

    $this->assertTrue($user->fresh()->hasVerifiedEmail());
    Event::assertDispatched(Verified::class);
});

test('email verification fails with invalid signature', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->get("/api/v1/email/verify/{$user->getKey()}/invalid-hash");

    $response->assertForbidden();
});

test('email verification view reports already verified if previously verified', function () {
    $user = User::factory()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->get($verificationUrl);

    $response->assertOk()
        ->assertViewIs('auth.verify-email')
        ->assertViewHas('status', 'already_verified');
});

test('authenticated user can request email verification notification', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/email/verification-notification');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Email verification link sent successfully',
        ]);
});

test('already verified user receives notice that email is already verified', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/email/verification-notification');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Email already verified',
        ]);
});
