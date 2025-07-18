<?php

namespace Rake\Contracts;

/**
 * Interface for resource managers (standardize resource CRUD and management)
 */
interface ResourceManagerInterface
{
    /**
     * Create a new resource
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update an existing resource
     * @param int|string $id
     * @param array $data
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * Delete a resource
     * @param int|string $id
     * @return bool
     */
    public function delete($id): bool;

    /**
     * Find a resource by ID
     * @param int|string $id
     * @return mixed
     */
    public function find($id);
}