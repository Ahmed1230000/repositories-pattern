<?php

namespace AhmedMahmoud\RepositoryPattern\Repositories;

use Packages\AhmedMahmoud\RepositoryPattern\Src\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseRepository
 *
 * Provides a base implementation of the RepositoryInterface
 * for common CRUD operations using an Eloquent model.
 */
class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model The Eloquent model instance.
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model The model to operate on.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve paginated list of models.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        return $this->model->paginate(20);
    }

    /**
     * Create a new model instance.
     *
     * @param array $data
     * @return Model
     */
    public function store(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Find a model by ID or fail.
     *
     * @param int $id
     * @return Model
     */
    public function show(int $id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Update a model by ID.
     *
     * @param int $id
     * @param array $data
     * @return Model|null
     */
    public function update(int $id, array $data)
    {
        $model = $this->model->findOrFail($id);
        if ($model) {
            $model->update($data);
        }
        return $model;
    }

    /**
     * Delete a model by ID.
     *
     * @param int $id
     * @return Model|null
     */
    public function destroy(int $id)
    {
        $model = $this->model->findOrFail($id);
        if ($model) {
            $model->delete();
        }
        return $model;
    }
}
