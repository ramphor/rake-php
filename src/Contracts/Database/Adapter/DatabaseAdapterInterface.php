<?php

namespace Rake\Contracts\Database\Adapter;

use Rake\Contracts\Database\DatabaseDriverInterface;

/**
 * Interface for database adapters (wrap a driver, expose API for core)
 */
interface DatabaseAdapterInterface
{
    /**
     * Get the underlying database driver instance.
     *
     * @return DatabaseDriverInterface
     */
    public function getDriver() : ? DatabaseDriverInterface;

    /**
     * Get a query builder for a table.
     *
     * @param string $table
     * @return mixed
     */
    public function table(string $table);

    /**
     * Insert data into a table.
     *
     * @param string $table
     * @param array $data
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int;

    /**
     * Update data in a table.
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $where): int;

    /**
     * Delete data from a table.
     *
     * @param string $table
     * @param array $where
     * @return int Number of affected rows
     */
    public function delete(string $table, array $where): int;

    /**
     * Select data from a table.
     *
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
     * Run a set of operations in a transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback);
}