<?php

namespace App\Repositories;

use App\Models\JobApplication;

class JobApplicationRepository extends BaseRepository implements JobApplicationRepositoryInterface
{
    public function __construct(JobApplication $model)
    {
        parent::__construct($model);
    }
}
