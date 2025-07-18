<?php

namespace Rake\Contracts\Database\Adapter;

use Rake\Contracts\Database\DatabaseDriverInterface;

/**
 * Interface for database adapters (wrap a driver, expose API for core)
 */
interface DatabaseAdapterInterface
{
    /**
     * DatabaseAdapter constructor.
     *
     * @param \Rake\Contracts\Database\DatabaseDriverInterface $driver The database driver instance to use for all operations.
     */
    public function __construct(DatabaseDriverInterface $driver);


    /**
     * Get the underlying database driver instance.
     *
     * @return \Rake\Contracts\Database\DatabaseDriverInterface
     */
    public function getDriver();

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
     * @return array
     */
    public function select(string $table, array $columns = ['*'], array $where = []): array;

    /**
     * Run a set of operations in a transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback);
}