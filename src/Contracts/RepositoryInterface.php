<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Src\Contracts;

interface RepositoryInterface
{
    /**
     * Summary of index
     * @return void
     */
    public function index();
    /**
     * Summary of store
     * @param array $data
     * @return void
     */
    public function store(array $data);
    /**
     * Summary of show
     * @param int $id
     * @return void
     */
    public function show(int $id);
    /**
     * Summary of update
     * @param int $id
     * @param array $data
     * @return void
     */
    public function update(int $id, array $data);
    /**
     * Summary of destroy
     * @param int $id
     * @return void
     */
    public function destroy(int $id);
}
