<?php

namespace App\Repositories;

use App\DTOs\Contracts\DataObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected Model $model)
    {
        //
    }

    public function all(): array|Collection
    {
        return $this->model->all();
    }

    public function find(mixed $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(mixed $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function create(array|DataObject $data): Model
    {
        $attributes = $data instanceof DataObject ? $data->toArray() : $data;

        return $this->model->create($attributes);
    }

    public function update(Model $model, array|DataObject $data): bool
    {
        $attributes = $data instanceof DataObject ? $data->toArray() : $data;

        return $model->update($attributes);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }
}
