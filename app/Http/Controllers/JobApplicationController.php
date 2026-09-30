<?php

namespace App\Http\Controllers;

use App\DTOs\JobApplicationData;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Http\Requests\UpdateJobApplicationRequest;
use App\Http\Resources\JobApplicationResource;
use App\Repositories\JobApplicationRepositoryInterface;
use App\Traits\ApiResponse;

class JobApplicationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly JobApplicationRepositoryInterface $repository) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $jobApplications = $this->repository->paginate();

        return $this->successResponse(JobApplicationResource::collection($jobApplications), 'Job applications retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJobApplicationRequest $request)
    {
        $dto = JobApplicationData::fromStoreRequest($request);
        $jobApplication = $this->repository->create($dto);

        return $this->successResponse(
            new JobApplicationResource($jobApplication),
            'Job application created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $jobApplication = $this->repository->findOrFail($id);

        return $this->successResponse(
            new JobApplicationResource($jobApplication),
            'Job application retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateJobApplicationRequest $request, string $id)
    {
        $jobApplication = $this->repository->findOrFail($id);
        $dto = JobApplicationData::fromUpdateRequest($request);
        $this->repository->update($jobApplication, $dto);

        return $this->successResponse(
            new JobApplicationResource($jobApplication->fresh()),
            'Job application updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $jobApplication = $this->repository->findOrFail($id);
        $this->repository->delete($jobApplication);

        return $this->successResponse(null, 'Job application deleted successfully');
    }
}
