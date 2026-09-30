<?php

namespace App\Repositories;

use App\DTOs\Contracts\DataObject;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function all(): array|Collection;

    public function find(mixed $id): ?Model;

    public function findOrFail(mixed $id): Model;

    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function create(array|DataObject $data): Model;

    public function update(Model $model, array|DataObject $data): bool;

    public function delete(Model $model): bool;
}
