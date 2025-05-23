<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Src\Contracts;

/**
 * Interface RepositoryInterface
 *
 * Defines the contract for repository CRUD operations.
 */
interface RepositoryInterface
{
    /**
     * Retrieve paginated list of resources.
     *
     * @return mixed
     */
    public function index();

    /**
     * Store a newly created resource.
     *
     * @param array $data
     * @return mixed
     */
    public function store(array $data);

    /**
     * Retrieve a single resource by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function show(int $id);

    /**
     * Update a resource by ID.
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update(int $id, array $data);

    /**
     * Delete a resource by ID.
     *
     * @param int $id
     * @return mixed
     */
    public function destroy(int $id);
}
