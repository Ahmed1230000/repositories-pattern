<?php

namespace AhmedMahmoud\RepositoryPattern\Repositories;

use Packages\AhmedMahmoud\RepositoryPattern\src\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements RepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Summary of index
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        return $this->model->paginate(20);
    }
    /**
     * Summary of store
     * @param array $data
     */
    public function store(array $data)
    {
        return $this->model->create($data);
    }
    /**
     * Summary of show
     * @param int $id
     */
    public function show(int $id)
    {
        return $this->model->findOrFail($id);
    }
    /**
     * Summary of update
     * @param int $id
     * @param array $data
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
     * Summary of destroy
     * @param int $id
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
