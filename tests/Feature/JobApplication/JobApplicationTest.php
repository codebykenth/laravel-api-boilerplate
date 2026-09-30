<?php

use App\Enums\JobStatus;
use App\Enums\JobType;
use App\Models\JobApplication;
use App\Models\User;

test('unauthenticated users cannot access job applications', function () {
    $this->getJson('/api/v1/job-applications')->assertUnauthorized();
    $this->postJson('/api/v1/job-applications', [])->assertUnauthorized();
});

test('authenticated user can view paginated job applications', function () {
    $user = User::factory()->create();
    JobApplication::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/job-applications');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Job applications retrieved successfully',
        ])
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => ['id', 'title', 'description', 'type', 'salary_range', 'location', 'status'],
            ],
        ]);
});

test('user can create a job application with valid data', function () {
    $user = User::factory()->create();

    $payload = [
        'title' => 'Senior Backend Engineer',
        'description' => 'Working with Laravel and distributed APIs',
        'type' => JobType::FullTime->value,
        'salary_range' => '$120,000 - $140,000',
        'location' => 'Remote',
        'status' => JobStatus::Applied->value,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/job-applications', $payload);

    $response->assertCreated()
        ->assertJson([
            'status' => 'success',
            'message' => 'Job application created successfully',
            'data' => [
                'title' => 'Senior Backend Engineer',
                'type' => JobType::FullTime->value,
                'status' => JobStatus::Applied->value,
            ],
        ])
        ->assertJsonStructure([
            'data' => ['id', 'title', 'description', 'type', 'salary_range', 'location', 'status'],
        ]);

    $this->assertDatabaseHas('job_applications', [
        'title' => 'Senior Backend Engineer',
        'user_id' => $user->id,
    ]);
});

test('creating job application validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/job-applications', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'description', 'type', 'salary_range', 'location', 'status']);
});

test('creating job application validates enum values', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/job-applications', [
            'title' => 'Engineer',
            'description' => 'Some description',
            'type' => 'invalid-type',
            'salary_range' => '$100k',
            'location' => 'Remote',
            'status' => 'invalid-status',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'status']);
});

test('user can view a single job application', function () {
    $user = User::factory()->create();
    $job = JobApplication::factory()->create(['user_id' => $user->id, 'title' => 'Staff Architect']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/job-applications/{$job->id}");

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $job->id,
                'title' => 'Staff Architect',
            ],
        ]);
});

test('viewing non-existent job application returns not found', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/job-applications/non-existent-ulid');

    $response->assertNotFound();
});

test('user can update a job application', function () {
    $user = User::factory()->create();
    $job = JobApplication::factory()->create([
        'user_id' => $user->id,
        'title' => 'Junior Dev',
        'status' => JobStatus::Wishlist,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/job-applications/{$job->id}", [
            'title' => 'Mid-level Dev',
            'description' => 'Promoted role',
            'type' => JobType::FullTime->value,
            'salary_range' => '$90k',
            'location' => 'Hybrid',
            'status' => JobStatus::Interview->value,
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Job application updated successfully',
            'data' => [
                'id' => $job->id,
                'title' => 'Mid-level Dev',
                'status' => JobStatus::Interview->value,
            ],
        ]);

    $this->assertEquals('Mid-level Dev', $job->fresh()->title);
    $this->assertEquals(JobStatus::Interview, $job->fresh()->status);
});

test('user can delete a job application', function () {
    $user = User::factory()->create();
    $job = JobApplication::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/job-applications/{$job->id}");

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'message' => 'Job application deleted successfully',
        ]);

    $this->assertDatabaseMissing('job_applications', [
        'id' => $job->id,
    ]);
});
