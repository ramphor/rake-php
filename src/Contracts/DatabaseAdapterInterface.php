<?php

namespace Rake\Contracts;

/**
 * Interface for database adapters (wrap a driver, expose API for core)
 */
interface DatabaseAdapterInterface
{
    /**
     * Insert data into table
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insert(string $table, array $data): bool;

    /**
     * Update data in table
     * @param string $table
     * @param array $data
     * @param array $where
     * @return bool
     */
    public function update(string $table, array $data, array $where): bool;

    /**
     * Delete data from table
     * @param string $table
     * @param array $where
     * @return bool
     */
    public function delete(string $table, array $where): bool;

    /**
     * Select data from table
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param int $limit
     * @param array $orderBy
     * @return array
     */
    public function select(string $table, array $columns = ['*'], array $where = [], int $limit = 0, array $orderBy = []): array;

    /**
     * Count records in table
     * @param string $table
     * @param array $where
     * @return int
     */
    public function count(string $table, array $where = []): int;

    /**
     * Migrate (create or update) table from schema definition
     * @param array $schema
     * @return bool
     */
    public function migrate(array $schema): bool;

    /**
     * Get the underlying database driver instance.
     *
     * @return \Rake\Contracts\Database\DatabaseDriverInterface
     */
    public function getDriver();
}