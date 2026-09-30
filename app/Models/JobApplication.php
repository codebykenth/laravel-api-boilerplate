<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Enums\JobType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'description',
    'type',
    'salary_range',
    'location',
    'status',
    'user_id',
])]
class JobApplication extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'type' => JobType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
