<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Enums\JobType;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(JobType::cases()),
            'salary_range' => '$80,000 - $120,000',
            'location' => fake()->city(),
            'status' => fake()->randomElement(JobStatus::cases()),
            'user_id' => User::factory(),
        ];
    }
}
