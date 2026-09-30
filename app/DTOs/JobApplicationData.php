<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Enums\JobStatus;
use App\Enums\JobType;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Http\Requests\UpdateJobApplicationRequest;

readonly class JobApplicationData implements DataObject
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?JobType $type = null,
        public ?string $salaryRange = null,
        public ?string $location = null,
        public ?JobStatus $status = null,
        public ?string $userId = null,
    ) {}

    public static function fromStoreRequest(StoreJobApplicationRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            type: JobType::from($request->validated('type')),
            salaryRange: $request->validated('salary_range'),
            location: $request->validated('location'),
            status: JobStatus::from($request->validated('status')),
            userId: $request->user()?->id,
        );
    }

    public static function fromUpdateRequest(UpdateJobApplicationRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            type: $request->has('type') ? JobType::from($request->validated('type')) : null,
            salaryRange: $request->validated('salary_range'),
            location: $request->validated('location'),
            status: $request->has('status') ? JobStatus::from($request->validated('status')) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type?->value,
            'salary_range' => $this->salaryRange,
            'location' => $this->location,
            'status' => $this->status?->value,
            'user_id' => $this->userId,
        ], fn ($value) => $value !== null);
    }
}
