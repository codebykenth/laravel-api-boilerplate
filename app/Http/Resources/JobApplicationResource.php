<?php

namespace App\Http\Resources;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobApplication
 */
class JobApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: string,
     *     title: string,
     *     description: string,
     *     type: string,
     *     salary_range: string,
     *     location: string,
     *     status: string,
     *     created_at: ?string,
     *     updated_at: ?string,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type?->value ?? $this->type,
            'salary_range' => $this->salary_range,
            'location' => $this->location,
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
